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

        const equal = await dbQuery(`SELECT * FROM autoreplies WHERE keyword = "${command}" AND type_keyword = 'Equal' AND device = ${sock.user.id.split(':')[0]}`);
        if (equal.length === 0) {
            // select locate
            result = await dbQuery(`SELECT * FROM autoreplies WHERE LOCATE(keyword, "${command}") > 0 AND type_keyword = 'Contain' AND device = ${sock.user.id.split(':')[0]}`);
        } else {
            result = equal;
        }

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
                return r.reply;
            })
        }
        // replace if exists {name} with sender name in reply
        for(let reply of replies){
            reply = JSON.parse(
                (()=>{
                    let replaced = JSON.stringify(reply)
                        .replace(/\{\{nama\}\}/g, contact.name)
                        .replace(/\{\{nomor\}\}/g, contact.number)
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

            if(typeof reply === 'string'){
                reply = JSON.parse(reply);
            }
            if(reply.buttons && !reply.buttons.length){
                delete reply.buttons;
            }

            let message = await sock.sendMessage(msg.key.remoteJid, reply).catch(e => console.log(e));
            log.info('Sending Autoreply Message to ' + msg.key.remoteJid?.split(':')[0] + '...');

            let timestamp = parseInt(message.messageTimestamp) + 2;

            const me = sock.user.id.split(':')[0];

            setTimeout(async () => {
                await generateChatQuery({
                    me,
                    from,
                    senderName,
                    messageId: message.key.id,
                    senderType: "AUTO_REPLY",
                    status: "PENDING",
                    item: reply,
                    timestamp,
                });
                await dbQuery(`INSERT INTO autoreply_messages (message_id) VALUES ("${message.key.id}")`)
            }, 3000);
        }

        //return;

    } catch (e) {
        console.log(e)
    }
}

const saveLiveChat = async function(msg, sock){
    try {

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
        if (type === 'imageMessage') {

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
            image = null;

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
        });
        // console.log(`Unread messages from ${from} to ${me}`);

    } catch (e){
        return;
    }
}

const generateChatQuery = async function({senderName, from, me, senderType, text, item, image, timestamp, messageId, status}){

    if(item){
        text = item.text ?? item.caption ?? '';
        image = item.image ?? null;
    } else {
        item = {};
    }
    let messageDateTime = (new Date(timestamp * 1000)).toISOString().replace('T', ' ').replace('\.000Z', '');

    const thisNumber = (await dbQuery(`SELECT * FROM numbers WHERE body = "${me}" AND live_chat = 1 LIMIT 1`))[0] ?? null;
    if(!thisNumber){
        throw "Number " + me + " is not registered as a live chat number";
    }
    let numberId = thisNumber.id;

    let currentConversation = await dbQuery(`SELECT * FROM conversations WHERE target_number = "${from}" AND device_number = "${me}" LIMIT 1`);
    if(!currentConversation.length){
        await dbQuery(`INSERT INTO conversations (target_number, number_id, target_name, device_number) VALUES ("${from}", "${numberId}", "${senderName}", "${me}")`);
        currentConversation = await dbQuery(`SELECT * FROM conversations WHERE target_number = "${from}" AND device_number = "${me}" LIMIT 1`);
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
        await dbQuery(`INSERT INTO chats (conversation_id, number_type, read_status, message, sent_at, message_id) VALUES ("${currentConversation[0].id}", "${senderType}", "UNREAD", '${JSON.stringify({
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