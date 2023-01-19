const cron = require("node-cron");
const {dbQuery, dbUpdateQuery, db} = require("./database");
const {formatReceipt} = require("./router/helper");
const fs = require("fs");
const {setStatus} = require("./database/index");
const {toQueryTimestamp} = require("./database");
const {connectWaBeforeSend, sendMessage} = require("./router/model/whatsapp");
const path = require("path");
const autoreplyTimeoutMs = 30000;

const sendCampaignBlasts = async function({sender, blasts, delay, id: campaignId}){
    for(let i in blasts){

        let {receiver, message, id} = blasts[i];

        try {
            let msg = await (new Promise((resolve, reject) => {
                setTimeout(() => {
                    dbQuery(`SELECT * FROM campaigns WHERE id = "${campaignId}"`).then(r => {
                        if (r.status === 'paused') {
                            reject({
                                reason: 'paused'
                            });
                        } else {
                            sock.get(sender).sendMessage(formatReceipt(receiver), message).then(r => {
                                resolve(r);
                            }).catch(e => {
                                console.log(message, e);
                                reject({
                                    reason: 'failed'
                                })
                            });
                        }
                    }).catch(e => {
                        console.log(e);
                    });

                }, i === '0' ? 0 : delay * 1000);
            }));
            log.info('[Blast Message]: A new message sent!');
            let timestamp = parseInt(msg.messageTimestamp) * 1000;
            dbUpdateQuery(`UPDATE blasts SET status = "success", updated_at = ${db.escape(new Date())} WHERE id = "${id}"`).catch(e=>{
                console.log(e);
            });
        } catch (e){
            if(e.reason === 'failed'){
                log.info('[Blast Message]: An error occurred during sending a message');
                dbUpdateQuery(`UPDATE blasts SET status = "failed", updated_at = ${db.escape(new Date())} WHERE id = "${id}"`).catch(e=>{});
            } else {
                return;
            }
        }
    }
    dbUpdateQuery(`UPDATE campaigns SET status = "finish" WHERE id = "${campaignId}"`).catch(e=>{
        console.log(e);
    });
}

const blastMessage = async function(){

    try {
        const onlineNumbers = Object.keys(sock.allInfo()).filter(o => sock.getInfo(o).isOnline);
        let escaped = db.escape(onlineNumbers);
        let dateNow = db.escape(new Date());
        if(!escaped.length){
            log.info('[Blast Message]: No Active Phone Number Found!');
            return;
        } else {
            log.info('[Blast Message]: Escaped: ' + escaped);
        }
        let campaigns = await dbQuery(`SELECT * FROM campaigns WHERE status = "waiting" AND sender IN (${escaped}) AND schedule <= ${dateNow}`);
        console.log(campaigns);
        if(!campaigns.length){
            log.info('[Blast Message]: No Campaigns Found!');
            return;
        }
        let ids = campaigns.map(function (c) {
            return c.id;
        });

        escaped = db.escape(ids);

        await dbUpdateQuery(`UPDATE campaigns SET status = "processing" WHERE id IN (${escaped})`);

        let blasts = await dbQuery(`SELECT * FROM blasts WHERE campaign_id IN (${escaped}) AND status = "pending"`);
        campaigns = campaigns.map(c => {
            c.blasts = blasts.filter(b => b['campaign_id'] === c.id);
            return c;
        });
        for(let campaign of campaigns){
            log.info('[Blast Message]: Sending campaign blasts from number ' + campaign.sender);
            sendCampaignBlasts(campaign).catch(e => {
                log.error('BLAST ERROR', e);
            });
        }
    } catch (e){
        log.error(e);
    }

};

const conversationMerger = function(){
    dbQuery('SELECT id, target_number, device_number FROM conversations').then(r => {

        const idCount = r.reduce((p, c)=>{
            let num = p[c.target_number + '-' + c.device_number] ?? {
                ids: [],
                count: 0,
            };
            return {
                ...p,
                [c.target_number + '-' + c.device_number]: {
                    ids: [...num.ids, c.id],
                    count: num.count + 1,
                },
            }
        }, {});

        Object.keys(idCount).filter(targetNumber => idCount[targetNumber].count > 1).map(targetNumber => {
            return idCount[targetNumber].ids;
        }).forEach(function(ids){
            dbUpdateQuery(`UPDATE chats
                           SET conversation_id = "${ids[0]}"
                            WHERE conversation_id IN (${db.escape(ids)})`).then(r =>{
                                dbUpdateQuery(`DELETE FROM conversations WHERE id IN (${db.escape(ids.slice(1))})`).catch(e=>log.error('Error on merging chats'));
            }).catch(e => {
                log.error('Error on merging chats');
            });
        });

    }).catch(e => {
        log.error('Some error happened during merging conversations');
    });
}

const autoReply = async function () {
    try {
        let pendingReplies = await dbQuery(`SELECT m.*, c.target_number as number, c.device_number as token FROM autoreply_messages m
                                             LEFT JOIN chats ch ON ch.message_id = m.replied_to_message_id
                                             LEFT JOIN conversations c ON c.id = ch.conversation_id
                                             WHERE m.status = "pending"`);
        let pendingIds = pendingReplies.map(p => p.id);
        await dbUpdateQuery(`UPDATE autoreply_messages SET status = "processing", updated_at = ${db.escape(new Date())} WHERE id IN (${db.escape(pendingIds)})`);
        for(let {id, number, token, prepared_message: message} of pendingReplies){
            let socket;
            if(typeof message === 'string'){
                message = JSON.parse(message);
            }
            log.info('[Auto Reply]: ' + token + ' is sending message...');
            if(!!(socket = sock.get(token))){
                socket.sendMessage(formatReceipt(number), message).then(msg => {
                    log.info('[Auto Reply]: ' + token + ' a message sent...');
                    dbUpdateQuery(`UPDATE autoreply_messages SET message_id = "${msg.key.id}", status = "success", updated_at = ${db.escape(new Date())} WHERE id = "${id}"`).catch(e => {
                        log.error('MySQL auto reply scheduler error');
                    });
                }).catch(e => {
                    log.info('[Auto Reply]: ' + token + ' an error occurred...');
                    dbUpdateQuery(`UPDATE autoreply_messages SET status = "failed", updated_at = ${db.escape(new Date())} WHERE id = "${id}"`).catch(e => {
                        log.error('MySQL auto reply scheduler error');
                    });
                });
            }
        }
    } catch (e) {

    }
}

const checkTimedOutAutoreplies = async function () {
    try {
        let messages = await dbQuery('SELECT id, updated_at FROM autoreply_messages WHERE status = "processing"');
        let ids = [];
        for(let message of messages){
            if(message.updated_at && (new Date(message.updated_at)).getTime() + autoreplyTimeoutMs < (new Date()).getTime()){
                ids.push(message.id);
            }
        }
        if(ids.length){
            await dbUpdateQuery(`UPDATE autoreply_messages SET status = "pending" WHERE status = "processing" AND id IN (${db.escape(ids)})`);
            log.info('Updated ' + ids.length + ' message statuses to processing.');
        }
    } catch (e){
        log.error('Some error occurred.')
    }
}

const checkSocket = function(){
    for(let number in sock.allInfo()){
        if(sock.getInfo(number).isOnline){
            log.info(`[Global Socket]: ${number} is Online`)
        }
    }
}

const numberCheck = function(){
    dbQuery('SELECT * FROM numbers').then(numbers => {
        numbers = numbers.filter(n => {
            let p = path.join(__dirname, '../credentials/' + formatReceipt(n.body).split('@')[0]);
            return fs.existsSync(p)? fs.readdirSync(p)?.length: false
        });
        const onlineNumbers = Object.keys(sock.allInfo()).filter(o => sock.getInfo(o).isOnline);
        for (let {body} of numbers.filter(n => !onlineNumbers.includes(n))) {
            connectWaBeforeSend(body).then(exists => {
                log.info(`Status (${body}): ${exists ? 'Connected' : 'Disconnected'}`);
                if (!exists) {
                    return setStatus(body, 'Disconnect');
                } else {
                    return setStatus(body, 'Connected');
                }
            }).then(r => {
                dbQuery('SELECT chats.*, numbers.body as device_number, target_number FROM chats JOIN conversations ON conversations.id = chats.conversation_id JOIN numbers ON numbers.body = conversations.device_number WHERE numbers.live_chat = 1 AND numbers.status = "Connected" AND message_id IS NULL').then(chats => {

                    for(let chat of chats){
                        sendMessage(chat['device_number'], chat['target_number'], chat.message).then(messageItem => {
                            if(!messageItem?.key){
                                return;
                            }
                            let messageId = messageItem.key.id;
                            let timestamp =  parseInt(messageItem.messageTimestamp);

                            dbUpdateQuery(`UPDATE chats SET message_id = "${messageId}", read_status = "DELIVERED", sent_at = "${toQueryTimestamp(timestamp * 1000)}" WHERE id = "${chat.id}" && message_id IS NULL`).catch(e=>{});
                        }).catch(e => {
                            log.error(e);
                        });
                    }

                }).catch(e=>{
                    console.log(e);
                });
            }).catch(e=>{
                console.log(e);
            });
        }
    }).catch(e => {
        console.log(e);
    });
}

const Scheduler = function(){

    const schedules = [
        {
            name: 'Blast Message',
            time: '*/3 * * * * *',
            task: blastMessage,
        },
        {
            name: 'Global Socket',
            time: '*/3 * * * * *',
            task: checkSocket,
        },
        {
            name: 'Auto Reply',
            time: '*/3 * * * * *',
            task: autoReply,
        },
        {
            name: 'Chat Merger',
            time: '*/5 * * * * *',
            task: conversationMerger,
        },
        {
            name: 'Auto Reply Timeout Handler',
            time: '*/3 * * * * *',
            task: checkTimedOutAutoreplies,
        },
        {
            name: 'Number Check',
            time: '* * * * *',
            task: numberCheck,
        }
    ];

    const init = function(logger = true){
        for(let {name, time, task} of schedules){
            cron.schedule(time, function(){
                if(logger)
                    log.info('Running Scheduler [' + name + ']')
                task();
            });
        }
    }

    return {
        init,
    };
}

module.exports = Scheduler;