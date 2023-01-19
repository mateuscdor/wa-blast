const {db, dbQuery } = require('../../database/index');
require('dotenv').config();
const {
    default: makeWASocket,
    downloadContentFromMessage
} = require('@adiwajshing/baileys')
const axios = require('axios');
const fs = require('fs');
const path = require("path");
const sizeOf = require('image-size');
const {dbUpdateQuery} = require("../../database");
const request = require("request");
const {convertFileTypes} = require("../helper");
const gm = require('gm').subClass({ imageMagick: true });

//const { startCon } = require('./WaConnection');
async function removeForbiddenCharacters(input) {
    let forbiddenChars = ['/', '?', '&', '=', '"']
    for (let char of forbiddenChars) {
        input = input.split(char).join('');
    }
    return input
}
const autoReply = async (msg, sock) => {

    try {
        if (msg.key.remoteJid === 'status@broadcast') return;

        const type = Object.keys(msg.message || {})[0]

        const body = (type === 'conversation' && msg.message.conversation) ? msg.message.conversation : (type == 'imageMessage') && msg.message.imageMessage.caption ? msg.message.imageMessage.caption : (type == 'videoMessage') && msg.message.videoMessage.caption ? msg.message.videoMessage.caption : (type == 'extendedTextMessage') && msg.message.extendedTextMessage.text ? msg.message.extendedTextMessage.text : (type == 'messageContextInfo') && msg.message.listResponseMessage?.title ? msg.message.listResponseMessage.title : (type == 'messageContextInfo') ? msg.message.buttonsResponseMessage.selectedDisplayText : ''
        const d = body.toLowerCase()
        const command = await removeForbiddenCharacters(d);

        const senderName = msg?.pushName || '';
        const from = msg.key.remoteJid.split('@')[0];
        let bufferImage;
        // if(msg && msg.image){
        //     let data = await (new Promise((resolve, reject) => {
        //         request.get(msg.image?.url, function (error, response, body) {
        //             if (!error && response.statusCode === 200) {
        //                 let data = Buffer.from(body,'base64');
        //                 resolve(data);
        //             } else {
        //                 reject("Image Not found");
        //             }
        //         });
        //     }))
        //     msg.jpegThumbnail = Buffer.from(await sharp(data).jpeg({
        //         quality: 30
        //     }).resize({ width: 100 }).toBuffer()).toString('base64');
        // }

        if (msg.key.fromMe === true) return;
        let replies;
        let result;

        result = await dbQuery(`SELECT * FROM autoreplies WHERE device = ${sock.user.id.split(':')[0]}`);
        result = result.filter(r => {
            return r.keyword.split('[|]').some(keyword => {
               if (r.type_keyword === 'Contain') {
                   return command.trim().toLowerCase().includes(keyword.trim().toLowerCase());
               } else if(r.type_keyword === 'Equal'){
                   return keyword.trim().toLowerCase() === command.trim().toLowerCase();
               } else if(r.type_keyword === 'None'){
                   return true;
               }
           });
        }).filter(r => {

            let settings = {};
            let allDays = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
            try {
                settings = typeof r.settings === 'object' ? r.settings: JSON.parse(r.settings);
                settings = {
                    startTime: `${settings?.startTime ?? '00:00'}`.substr(0, 5),
                    endTime: `${settings?.endTime ?? '24:00'}`.substr(0, 5),
                    activeDays: settings?.activeDays ?? allDays,
                }
            } catch (e){
                settings = {
                    startTime: '00:00',
                    endTime: '24:00',
                    activeDays: allDays
                };
            }
            let now = new Date();
            let day = allDays[(now.getDay() + 1) % 7];
            now = [now.getHours().toString().padStart(2, '0'), now.getMinutes().toString().padStart(2, '0')].join(':');
            return now.localeCompare(settings.startTime) > 0
                && now.localeCompare(settings.endTime) < 0
                && settings.activeDays.includes(day);
        });

        let destinationNumber = msg?.key?.remoteJid;
        let contact = {
            name: senderName,
            number: destinationNumber,
            raw_values: '[]',
        }
        if(destinationNumber){
            destinationNumber = destinationNumber.split('@')[0];
            const user = await dbQuery(`SELECT user_id FROM numbers WHERE body = "${sock.user.id.split(':')[0]}"`)
            if(user?.length){
                let userId = user[0].user_id;
                let contactQuery = await dbQuery(`SELECT * FROM contacts WHERE number = "${destinationNumber}" AND user_id = ${userId}`)
                contact = contactQuery[0] ?? contact;
            }
        }


        //
        if (result.length === 0) {
            const me = sock.user.id.split(':')[0];

            const getUrl = await dbQuery(`SELECT webhook FROM numbers WHERE body = '${me}' LIMIT 1`);
            const url = getUrl[0]?.webhook;
            if (url === undefined || url === null) return;
            const r = await sendWebhook({ command: d, bufferImage, from, url });
            if (r === false) return;
            replies = [JSON.stringify(r)];
        } else {
            replies = result.filter(res => {
                return res.reply_when === 'All' ? true : res.reply_when === 'Group' && msg.key.remoteJid.includes('@g.us') ? true : res.reply_when === 'Personal' && !msg.key.remoteJid.includes('@g.us');
            }).map(r => {
                return {
                    id: r.id,
                    reply: r.reply,
                    type_keyword: (!r.keyword.length? 'None': r.type_keyword)
                };
            })
        }

        if(replies.length){
            let some = replies.some(r => r.type_keyword !== 'None');
            if(some){
                replies = replies.filter(r => r.type_keyword !== 'None');
            }
        }
        // replace if exists {name} with sender name in reply
        for(let reply of replies){
            let raw = reply;
            reply = reply.reply;

            if(typeof reply === 'string'){
                reply = JSON.parse(reply);
            }

            reply = JSON.parse(
                (()=>{
                    let replaced = JSON.stringify(reply)
                        .replace(/\{\{nama\}\}/g, contact.name)
                        .replace(/\{\{nomor\}\}/g, contact.number.split('@')[0])
                        .replace(/\{\{var\1([0-9]+)\}\}/g, function(match){
                            let id = match.replace(/\{\{var(.*)\}\}/, '$1');
                            try {
                                return JSON.parse(contact.raw_values)[parseInt(id) + 1] ?? '';
                            } catch (e){
                                return '';
                            }
                        })

                    let matches = replaced.match(/(\{\{([\w\s]*([|][\w\s]*)*)\}\})/gi) ?? [];

                    matches.forEach(item => {
                        let str = item.replace(/(\{\{([\w\s]*([|][\w\s]*)*)\}\})/gi, '$2');
                        let split = str.split('\|').filter(s => !!s);
                        let replacedItem = split[Math.floor(Math.random() * split.length) % split.length] ?? '';
                        replaced = replaced.replace(item, replacedItem);
                    })

                    return replaced;
                })()
            )


            if(reply.buttons && !reply.buttons.length){
                delete reply.buttons;
            }
            if(reply.templateButtons && !reply.templateButtons.length){
                delete reply.templateButtons;
            }

            reply = convertFileTypes(reply);

            let receivedAt = parseInt(msg?.messageTimestamp) * 1000;
            let insertId = '';
            if(raw.type_keyword !== 'None'){
                let {insertId: id} = await dbQuery(`INSERT INTO autoreply_messages (autoreply_id, replied_to_message_id, status, prepared_message, created_at, updated_at, received_at) VALUES ("${raw.id}", "${msg.key.id}", "processing", '${JSON.stringify(reply)}', ${db.escape(new Date())}, ${db.escape(new Date())}, ${db.escape(new Date(receivedAt))})`);
                insertId = id;
            }
            log.info('Sending Autoreply Message to ' + msg.key.remoteJid?.split(':')[0] + '...');

            sock.sendMessage(msg.key.remoteJid, {
                ...reply,
                headerType: 4,
            }, {
                ...(reply && 'document' in reply? {url: reply.document}: {}),
                mediaUploadTimeoutMs: 11000
            }).then(message => {
                let timestamp = parseInt(message?.messageTimestamp ?? ((new Date()).getTime() / 1000)) + 2;
                const me = sock.user.id.split(':')[0];
                let isGroup = msg.key.remoteJid?.includes('@g') ?? msg.key.participant ?? msg.participant;

                setTimeout(async () => {
                    await generateChatQuery({
                        me,
                        from,
                        senderName,
                        messageId: message.key.id,
                        senderType: "AUTO_REPLY",
                        status: "PENDING",
                        item: reply,
                        isGroup: isGroup,
                        timestamp,
                    });
                }, 3000);
                if(raw.type_keyword === 'None') {
                    return;
                }
                dbUpdateQuery(`UPDATE autoreply_messages
                                   SET sent_at    = ${db.escape(new Date(timestamp * 1000))},
                                       message_id = "${message.key.id}",
                                       status     = "success",
                                       updated_at = ${db.escape(new Date())}
                                   WHERE autoreply_id = "${raw.id}"
                                     AND replied_to_message_id = "${msg.key.id}"
                                     AND id = "${insertId}"`).catch(e => {
                    log.error('MySql Error (autoreply updating error)');
                });

            }).catch(e => {
                log.error(`Error sending autoreply message (autoreply_id = ${raw.id})`);
                log.error(e);

                if(raw.type_keyword === 'None') {
                    return;
                }
                dbUpdateQuery(`UPDATE autoreply_messages
                                   SET status     = "failed",
                                       updated_at = ${db.escape(new Date())}
                                   WHERE autoreply_id = "${raw.id}"
                                     AND replied_to_message_id = "${msg.key.id}"
                                     AND id = "${insertId}"`).catch(e => {
                    log.error('MySql Error (autoreply updating error)');
                });

            });
        }

        //return;

    } catch (e) {
        log.error('autoreply error' + JSON.stringify(e));
    }
}

const saveLiveChat = async function(msg, sock, token){
    try {

        let isGroup = msg.key.remoteJid?.includes('@g') ?? msg.key.participant ?? msg.participant;

        if(isGroup){
            return;
        }
        let number = await dbQuery(`SELECT * FROM numbers WHERE live_chat = 1 && body = "${token}"`);
        if(!number?.length){
            return;
        }

        let fromMe = msg.key.fromMe;
        let senderType = "RECEIVER";

        if (msg.key.remoteJid === 'status@broadcast'){
            senderType = "BROADCAST";
        } else if(fromMe){
            senderType = "SENDER";
        }

        const type = Object.keys(msg.message || {})[0]

        const body = (type === 'conversation' && msg.message.conversation) ? msg.message.conversation : (type == 'imageMessage') && msg.message.imageMessage.caption ? msg.message.imageMessage.caption : (type == 'videoMessage') && msg.message.videoMessage.caption ? msg.message.videoMessage.caption : (type == 'extendedTextMessage') && msg.message.extendedTextMessage.text ? msg.message.extendedTextMessage.text : (type == 'messageContextInfo') && msg.message.listResponseMessage?.title ? msg.message.listResponseMessage.title : (type == 'messageContextInfo') ? msg.message.buttonsResponseMessage.selectedDisplayText : ''
        const command = await removeForbiddenCharacters(body);
        const senderName = msg?.pushName || '';
        const from = msg.key.remoteJid.split('@')[0];

        let image;
        //  const urlImage = (type == 'imageMessage') && msg.message.imageMessage.caption ? msg.message.imageMessage.caption : null;
        if (type === 'imageMessage' && !fromMe) {

            const stream = await downloadContentFromMessage(msg.message.imageMessage, 'image');
            let buffer = Buffer.from([])
            for await (const chunk of stream) {
                buffer = Buffer.concat([buffer, chunk])
            }
            let randomFileName = Math.random().toString(16).substr(2, 32) + '.' + sizeOf(buffer).type;
            let folderPath = path.join(__dirname, '../../../storage/app/public/chat-media/');
            if(!fs.existsSync(folderPath)){
                fs.mkdirSync(folderPath);
            }
            folderPath += from;
            if(!fs.existsSync(folderPath)){
                fs.mkdirSync(folderPath);
            }
            let fileName = randomFileName;
            fs.writeFile(folderPath + '/' + fileName, buffer, function(err){
                if(err){
                    throw err;
                }
            });

            image = process.env.APP_URL + `/storage/chat-media/${from}/${fileName}`;
        } else {
            if(fromMe && type === 'imageMessage'){
                image = null;
            } else {
                image = null;
            }
        }

        const me = sock.user.id.split(':')[0];
        let timestamp = fromMe? parseInt(msg.messageTimestamp) + 2: msg.messageTimestamp;

        await generateChatQuery({
            me,
            from,
            senderName,
            messageId: msg.key.id,
            senderType,
            text: command,
            image,
            timestamp,
            isGroup: msg.key?.participant,
        });
        // console.log(`Unread messages from ${from} to ${me}`);

    } catch (e){
        return;
    }
}

const generateChatQuery = async function({senderName, from, me, senderType, text, item, image, timestamp, messageId, status, isGroup}){

    if(item){
        text = item.text ?? item.caption ?? '';
        image = item.image ?? null;
    } else {
        item = {};
    }
    let messageDateTime = (new Date(timestamp * 1000)).toISOString().replace('T', ' ').replace('\.000Z', '');

    const thisNumber = (await dbQuery(`SELECT * FROM numbers WHERE body = "${me}" LIMIT 1`))[0] ?? null;
    if(!thisNumber){
        throw "Number " + me + " is not registered as a live chat number";
    }
    let numberId = thisNumber.id;

    let [conv] = await dbQuery(`SELECT id FROM conversations WHERE target_number = "${from}" AND device_number = "${me}" LIMIT 1`);
    let id = conv?.id;
    if(!id){
        let {insertId} = await dbQuery(`INSERT INTO conversations (target_number, number_id, target_name, device_number, is_group_chat, created_at, updated_at) VALUES ("${from}", "${numberId}", "${senderName}", "${me}", ${isGroup? 1: 0}, ${db.escape(new Date())}, ${db.escape(new Date())})`);
        id = insertId;
    } else {
        await dbUpdateQuery(`UPDATE conversations SET updated_at = ${db.escape(new Date())} WHERE id = ${id}`)
    }
    let exists = await dbQuery(`SELECT id FROM chats WHERE message_id = "${messageId}"`);
    if(exists.length){
        if(senderType === "AUTO_REPLY"){
            await dbUpdateQuery(`UPDATE chats SET read_status = "DELIVERED", number_type = "AUTO_REPLY", message = '${JSON.stringify({
                text,
                ...(image? {image: image}: {}),
                ...item
            })}' WHERE message_id = "${messageId}"`)
        } else {
            await dbUpdateQuery(`UPDATE chats SET read_status = "DELIVERED", message = '${JSON.stringify({
                text,
                ...(image? {image: image}: {}),
                ...item
            })}' WHERE message_id = "${messageId}"`)
        }
    } else {
        await dbQuery(`INSERT INTO chats (conversation_id, number_type, read_status, message, sent_at, message_id) VALUES ("${id}", "${senderType}", "UNREAD", '${JSON.stringify({
            text,
            ...(image? {image: image}: {}),
            ...item
        })}', "${messageDateTime}", "${messageId}")`)
    }
}


async function sendWebhook({ command, bufferImage, from, url }) {
    try {
        const data = {
            message: command,
            bufferImage: bufferImage,
            from: from
        }
        const headers = { 'Content-Type': 'application/json; charset=utf-8' }
        const res = await axios.post(url, data, headers).catch(() => {
            return false;
        })
        return res.data;
    } catch (error) {
        console.log(error)
        return false;
    }

}

module.exports = { autoReply, saveLiveChat };