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
const sharp = require("sharp");
const request = require("request");

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
        if(msg && msg.image){
            let data = await (new Promise((resolve, reject) => {
                request.get(msg.image?.url, function (error, response, body) {
                    if (!error && response.statusCode === 200) {
                        let data = Buffer.from(body,'base64');
                        resolve(data);
                    } else {
                        reject("Image Not found");
                    }
                });
            }))
            msg.jpegThumbnail = Buffer.from(await sharp(data).jpeg({
                quality: 30
            }).resize({ width: 100 }).toBuffer()).toString('base64');
        }
        if (msg.key.fromMe === false) return;
        let reply;

        let result;
        const equal = await dbQuery(`SELECT * FROM autoreplies WHERE keyword = "${command}" AND type_keyword = 'Equal' AND device = ${sock.user.id.split(':')[0]} LIMIT 1`);
        if (equal.length === 0) {
            // select locate
            result = await dbQuery(`SELECT * FROM autoreplies WHERE LOCATE(keyword, "${command}") > 0 AND type_keyword = 'Contain' AND device = ${sock.user.id.split(':')[0]} LIMIT 1`);
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
            reply = JSON.stringify(r);
        } else {

            let replyorno = result[0].reply_when === 'All' ? true : result[0].reply_when === 'Group' && msg.key.remoteJid.includes('@g.us') ? true : result[0].reply_when === 'Personal' && !msg.key.remoteJid.includes('@g.us');

            if (replyorno === false) return;
          reply = result[0].reply;
          //  reply = process.env.TYPE_SERVER === 'hosting' ? result[0].reply : JSON.stringify(result[0].reply);

        }
        // replace if exists {name} with sender name in reply
        let date = new Date();
        let hour = date.getHours();
        let hello = "Selamat Malam";
        if(hour >= 7 && hour < 12){
            hello = "Selamat Pagi";
        } else if(hour >= 12 && hour < 15){
            hello = "Selamat Siang";
        } else if(hour <= 18){
            hello = "Selamat Sore";
        }
        reply = JSON.parse(
            JSON.stringify(reply)
                .replace(/{nama}/g, contact.name)
                .replace(/{halo}/g, hello)
                .replace(/\{var\1([0-9]+)\}/g, function(match){
                    let id = match.replace(/\{var(.*)\}/, '$1');
                    return JSON.parse(contact.raw_values)[parseInt(id) + 1] ?? '';
                })
        )
        let message = await sock.sendMessage(msg.key.remoteJid, reply);

        let timestamp = parseInt(message.messageTimestamp) + 2;

        const me = sock.user.id.split(':')[0];

        await generateChatQuery({
            me,
            from,
            senderName,
            messageId: message.key.id,
            senderType: "AUTO_REPLY",
            status: "PENDING",
            text: reply.text,
            image: null,
            timestamp,
        });
        await dbQuery(`INSERT INTO autoreply_messages (message_id) values ("${message.key.id}")`);
        return message.key.id;

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

        if(fromMe){
            return;
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
        console.log(e);
        return;
    }
}

const generateChatQuery = async function({senderName, from, me, senderType, text, image, timestamp, messageId}){

    let messageDateTime = (new Date(timestamp * 1000)).toISOString().replace('T', ' ').replace('\.000Z', '');

    const thisNumber = (await dbQuery(`SELECT * FROM numbers WHERE body = "${me}" AND live_chat = 1 LIMIT 1`))[0] ?? null;
    if(!thisNumber){
        throw "Number " + me + " is not registered as a live chat number";
    }
    let numberId = thisNumber.id;

    let currentConversation = await dbQuery(`SELECT * FROM conversations WHERE target_number = "${from}" AND number_id = "${numberId}" LIMIT 1`);
    if(!currentConversation.length){
        await dbQuery(`INSERT INTO conversations (target_number, number_id, target_name, device_number) VALUES ("${from}", "${numberId}", "${senderName}", "${me}")`);
        currentConversation = await dbQuery(`SELECT * FROM conversations WHERE target_number = "${from}" AND number_id = "${numberId}" LIMIT 1`);
    }
    let exists = await dbQuery(`SELECT id FROM chats WHERE message_id = "${messageId}"`);
    if(exists.length){
        if(senderType === "AUTO_REPLY"){
            await dbUpdateQuery(`UPDATE chats SET read_status = "DELIVERED", number_type = "AUTO_REPLY" WHERE message_id = "${messageId}"`)
        } else {
            await dbUpdateQuery(`UPDATE chats SET read_status = "DELIVERED" WHERE message_id = "${messageId}"`)
        }
    } else {
        await dbQuery(`INSERT INTO chats (conversation_id, number_type, read_status, message, sent_at, message_id) VALUES ("${currentConversation[0].id}", "${senderType}", "UNREAD", '${JSON.stringify({
            text,
            ...(image? {image: image}: {}),
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