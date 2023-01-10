const cron = require("node-cron");
const {dbQuery, dbUpdateQuery, db} = require("./database");
const {formatReceipt} = require("./router/helper");

const sendCampaignBlasts = async function({sender, blasts, delay, id: campaignId}){
    for(let i in blasts){

        let {receiver, message, id} = blasts[i];

        try {
            await (new Promise((resolve, reject) => {
                setTimeout(() => {
                    dbQuery(`SELECT * FROM campaigns WHERE id = "${campaignId}"`).then(r => {
                       if(r.status === 'paused'){
                           reject({
                               reason: 'paused'
                           });
                       } else {
                           sock.get(sender).sendMessage(formatReceipt(receiver), message).then(r =>{
                               resolve(r);
                           }).catch(e => {
                               reject({
                                   reason: 'failed'
                               })
                           });
                       }
                    }).catch(e => {
                        console.log(e);
                    });

                }, i === '0'? 0: delay * 1000);
            }))
            log.info('[Blast Message]: A new message sent!');
            dbUpdateQuery(`UPDATE blasts SET status = "success", updated_at = ${db.escape(new Date())} WHERE id = "${id}"`).catch(e=>{});
        } catch (e){
            if(e.reason === 'failed'){
                log.info('[Blast Message]: An error occurred during sending a message');
                dbUpdateQuery(`UPDATE blasts SET status = "failed", updated_at = ${db.escape(new Date())} WHERE id = "${id}"`).catch(e=>{});
            } else {
                return;
            }
        }
    }
    dbUpdateQuery(`UPDATE campaigns SET status = "finish" WHERE id = "${campaignId}"`).catch(e=>{});
}
const blastMessage = async function(){

    try {
        const onlineNumbers = Object.keys(sock.allInfo()).filter(o => sock.getInfo(o).isOnline);
        let escaped = db.escape(onlineNumbers);
        let dateNow = db.escape(new Date());
        if(!escaped.length){
            log.info('[Blast Message]: No Active Phone Number Found!');
            return;
        }
        let campaigns = await dbQuery(`SELECT * FROM campaigns WHERE status = "waiting" AND sender IN (${escaped}) AND schedule <= ${dateNow}`, onlineNumbers);
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

const checkSocket = function(){
    for(let number in sock.allInfo()){
        if(sock.getInfo(number).isOnline){
            log.info(`[Global Socket]: ${number} is Online`)
        }
    }
}

const Scheduler = function(){

    const schedules = [
        {
            name: 'Blast Message',
            time: '*/10 * * * * *',
            task: blastMessage,
        },
        {
            name: 'Global Socket',
            time: '*/3 * * * * *',
            task: checkSocket,
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