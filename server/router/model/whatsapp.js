'use strict'

const { default: makeWASocket, makeWALegacySocket, downloadContentFromMessage } = require('@adiwajshing/baileys')
const { fetchLatestBaileysVersion, useMultiFileAuthState, makeCacheableSignalKeyStore } = require('@adiwajshing/baileys')
const { DisconnectReason } = require('@adiwajshing/baileys')
const ObjectManager = require("./object-manager");

// const logger = require('../../lib/pino')
const lib = require('../../lib')
const fs = require('fs')
let sock = ObjectManager();
let QR = ObjectManager();
let intervalStore = ObjectManager();
const { setStatus } = require('../../database/index')
const { autoReply, saveLiveChat} = require('./autoreply')
const { formatReceipt } = require('../helper')
const axios = require('axios')

/***********************************************************
 * FUNCTION
 **********************************************************/
//  import { Boom } from '@hapi/boom'
//  import makeWASocket, { AnyMessageContent, delay, DisconnectReason, fetchLatestBaileysVersion, makeInMemoryStore, MessageRetryMap, useMultiFileAuthState } from '../src'
const MAIN_LOGGER = require('../../lib/pino')
const request = require("request").defaults({ encoding: null });
const sharp = require('sharp');
const {dbQuery} = require("../../database");
const {onConnectionOpen, onConnectionClose, onQRConnection, onConnectionStart} = require("./events");

const logger = MAIN_LOGGER.child({})
//  logger.level = 'trace'

const useStore = !process.argv.includes('--no-store')

// external map to store retry counts of messages when decryption/encryption fails
// keep this out of the socket itself, so as to prevent a message decryption/encryption loop across socket restarts
const msgRetryCounterMap = () => MessageRetryMap = {}

// start a connection
const connectToWhatsApp = async (token, io = null) => {

    return new Promise((resolve, reject) => {
        let qrCode = QR.get(token);
        if (qrCode) {
            if (io !== null) {
                io.emit('qrcode', { token, data: qrCode, message: "please scan with your Whatsapp Account" })
            }
            return {
                status: false,
                sock: sock.get(token),
                qrcode: qrCode,
                message: "Please scann qrcode"
            }
        }

        let _interval = setTimeout(async () => {
            try {
                await onConnectionStart({sock, io, token});
                // fetch latest version of Chrome For Linux
                const chrome = await getChromeLates()
                //  console.log(`using Chrome v${chrome?.data?.versions[0]?.version}, isLatest: ${chrome?.data?.versions.length > 0 ? true : false}`)
                console.log('You re using whatsapp gateway M Pedia v4.3.2 - Contact admin if any trouble : 082298859671');
                // fetch latest version of WA Web
                const { version, isLatest } = await fetchLatestBaileysVersion()
                console.log(`using WA v${version.join('.')}, isLatest: ${isLatest}`)

                const {state, saveCreds} = await useMultiFileAuthState(`./credentials/${token}`)
                console.log(state);
                let socket = makeWASocket({
                    version,
                    // browser: ['Linux', 'Chrome', '103.0.5060.114'],
                    browser: ['M Pedia', 'Chrome', chrome?.data?.versions[0]?.version],
                    logger,
                    printQRInTerminal: true,
                    auth: {
                        creds: state.creds,
                        keys: makeCacheableSignalKeyStore(state.keys, logger)
                    }
                });
                sock.set(token, socket);
                sock.get(token).ev.process(
                    async (events) => {
                        if (events['connection.update']) {
                            const update = events['connection.update'];
                            const {connection, lastDisconnect, qr} = update;

                            if (connection === 'close') {
                                return onConnectionClose({lastDisconnect, io, sock, token, clearConnection, qr: QR, loop: ()=>connectToWhatsApp(token, io)});
                            }
                            if (qr) {
                                // SEND TO YOUR CLIENT SIDE
                                onQRConnection({io, token, qrCode: qr, qr: QR})
                            }
                            if (connection === 'open') {
                                await onConnectionOpen({io, token, qr: QR, sock})
                            }
                        }

                        if (events['messages.upsert']) {
                            const event = events['messages.upsert'];
                            if (!event.messages) {
                                return;
                            }
                            for (let message of event.messages) {
                                saveLiveChat(message, sock.get(token));
                                autoReply(message, sock.get(token));
                            }
                        }

                        if (events['creds.update']) {
                            saveCreds().catch(e => {
                            })
                        }
                    }
                )
                clearTimeout(_interval);
                resolve({
                    sock: sock.get(token),
                    qrcode: QR.get(token)
                });
            } catch (e) {
            }
        }, 3000);
    });
}
//
async function connectWaBeforeSend(token) {
    let status = undefined;
    let connect;
    connect = await connectToWhatsApp(token)

    await connect.sock.ev.on('connection.update', (con) => {
        const { connection, qr } = con
        if (connection === 'open') {
            status = true;
        }
        if (qr) {
            status = false;
        }
    })
    let counter = 0
    while (typeof status === 'undefined') {
        counter++
        if (counter > 4) {

            break
        }
        await new Promise(resolve => setTimeout(resolve, 1000));
    }

    return status;
}
// text message
const sendText = async (token, number, text) => {

    try {
        // awaiting sending message
        return await sock.get(token).sendMessage(formatReceipt(number), {text: text})
    } catch (error) {
        console.log(error)
        return false
    }

}
const sendMessage = async (token, number, msg) => {

    try {
        let message = JSON.parse(msg);

        if(message.image){
            let data = await (new Promise((resolve, reject) => {
                request.get(message.image.url, function (error, response, body) {
                    if (!error && response.statusCode === 200) {
                        let data = Buffer.from(body,'base64');
                        resolve(data);
                    } else {
                        reject("Image Not found");
                    }
                });
            }))
            message.jpegThumbnail = Buffer.from(await sharp(data).jpeg({
                quality: 30
            }).resize({ width: 100 }).toBuffer()).toString('base64');
        }
         // awaiting sending message
        return await sock.get(token).sendMessage(formatReceipt(number), message)

    } catch (error) {
        return false
    }

}

// media
async function sendMedia(token, destination, type, url, fileName, caption) {

    /**
     * type is "url" or "local"
     * if you use local, you must upload into src/public/temp/[fileName]
     */
    const number = formatReceipt(destination);
    try {
        if (type == 'image') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { image: url ? { url } : fs.readFileSync('src/public/temp/' + fileName), caption: caption ? caption : null },
            )
        } else if (type == 'video') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { video: url ? { url } : fs.readFileSync('src/public/temp/' + fileName), caption: caption ? caption : null },
            )
        } else if (type == 'audio') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { audio: url ? { url } : fs.readFileSync('src/public/temp/' + fileName), caption: caption ? caption : null },
            )
        } else if (type == 'pdf') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/pdf' },
                { url: url }
            )
        } else if (type == 'xls') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/excel' },
                { url: url }
            )
        } else if (type == 'xls') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/excel' },
                { url: url }
            )
        } else if (type == 'xlsx') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
                { url: url }
            )
        } else if (type == 'doc') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/msword' },
                { url: url }
            )
        } else if (type == 'docx') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
                { url: url }
            )
        } else if (type == 'zip') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/zip' },
                { url: url }
            )
        } else if (type == 'mp3') {
            var sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/mp3' },
                { url: url }
            )
        } else {
            console.log('Please add your won role of mimetype')
            return false
        }
        // console.log(sendMsg)
        return sendMsg
    } catch (error) {
        console.log(error)
        return false
    }

}

// button message
async function sendButtonMessage(token, number, button, message, footer, image) {

    /**
     * type is "url" or "local"
     * if you use local, you must upload into src/public/temp/[fileName]
     */
    let type = 'url'
    try {

        const buttons = button.map((x, i) => {
            console.log(x);
            return { buttonId: i, buttonText: { displayText: x.displayText }, type: 1 }
        })
        if (image) {
            var buttonMessage = {
                image: type == 'url' ? { url: image } : fs.readFileSync('src/public/temp/' + image),
                // jpegThumbnail: await lib.base64_encode(),
                caption: message,
                footer: footer,
                buttons: buttons,
                headerType: 4
            }
        } else {
            var buttonMessage = {
                text: message,
                footer: footer,
                buttons: buttons,
                headerType: 1
            }
        }
        const sendMsg = await sock.get(token).sendMessage(formatReceipt(number), buttonMessage)
        return sendMsg
    } catch (error) {
        console.log(error)
        return false
    }

}


async function sendTemplateMessage(token, number, button, text, footer, image) {

    try {
        // const templateButtons = [
        //     { index: 1, urlButton: { displayText: button[0].displayText, url: button[0].url } },
        //     { index: 2, callButton: { displayText: button[1].displayText, phoneNumber: button[1].phoneNumber } },
        //     { index: 3, quickReplyButton: { displayText: button[2].displayText, id: button[2].id } },
        // ]
      

        if (image) {
            var buttonMessage = {
                caption: text,
                footer: footer,
                templateButtons: button,
                image: { url: image },
                viewOnce: true
            }
        } else {
            var buttonMessage = {
                text: text,
                footer: footer,
                templateButtons: button,
                viewOnce: true
            }
        }

        const sendMsg = await sock.get(token).sendMessage(formatReceipt(number), buttonMessage)
        return sendMsg
    } catch (error) {
        console.log(error)
        return false
    }

}

// list message
async function sendListMessage(token, number, list, text, footer, title, buttonText) {

    try {

        const listMessage = { text, footer, title, buttonText, sections: [list] }

        const sendMsg = await sock.get(token).sendMessage(formatReceipt(number), listMessage)
        return sendMsg
    } catch (error) {
        console.log(error)
        return false
    }

}

// feetch group

async function fetchGroups(token) {
    // check is exists token
    try {
        let getGroups = await sock.get(token).groupFetchAllParticipating();
        let groups = Object.entries(getGroups).slice(0).map(entry => entry[1]);
     

        return groups
    } catch (error) {
        return false
    }
}

// if exist
async function isExist(token, number) {

    if (!sock.get(token)) {
        const status = await connectWaBeforeSend(token)
        if (!status) {
            return false
        }
    }
    try {
        if (number.includes('@g.us')) {
            return true
        } else {
            const [result] = await sock.get(token).onWhatsApp(number)
            return result
        }
    } catch (error) {
        return false
    }

}

// ppUrl

// close connection
async function deleteCredentials(token, io = null) {
    if (io !== null) {
        io.emit('message', { token: token, message: 'Logging out..' })
    }
    try {
        if (typeof sock.get(token) === 'undefined') {
            const status = await connectWaBeforeSend(token)
            if (status) {
                sock.get(token).logout()
                sock.remove(token)
            }
        } else {
            sock.get(token).logout()
            sock.remove(token)
        }
        QR.remove(token);
        clearInterval(intervalStore.get(token))
        setStatus(token, 'Disconnect')

        if (io != null) {
            io.emit('Unauthorized', token)
            io.emit('message', { token: token, message: 'Connection closed. You are logged out.' })
        }
        if (fs.existsSync(`./credentials/${token}`)) {
            fs.rmSync(`./credentials/${token}`, { recursive: true, force: true }, (err) => {
                if (err) console.log(err)
            })
            // fs.unlinkSync(`./sessions/session-${device}.json`)
        }

        // fs.rmdir(`credentials/${token}`, { recursive: true }, (err) => {
        //     if (err) {
        //         throw err;
        //     }
        //     console.log(`credentials/${token} is deleted`);
        // });

        return {
            status: true, message: 'Deleting session and credential'
        }
    } catch (error) {
        console.log(error);
        return {
            status: true, message: 'Nothing deleted'
        }
    }
}

async function getChromeLates() {
    return await axios.get('https://versionhistory.googleapis.com/v1/chrome/platforms/linux/channels/stable/versions')
}

function clearConnection(token) {
    clearInterval(intervalStore.get(token))

    sock.remove(token);
    QR.remove(token);
    setStatus(token, 'Disconnect');
    if (fs.existsSync(`./credentials/${token}`)) {
        fs.rmSync(`./credentials/${token}`, { recursive: true, force: true }, (err) => {
            if (err) console.log(err)
        })
        console.log(`credentials/${token} is deleted`);
    }
    // fs.rmdir(`credentials/${token}`, { recursive: true }, (err) => {
    //     if (err) {
    //         throw err;
    //     }
    //     console.log(`credentials/${token} is deleted`);
    // });
}

async function initialize(req, res) {
    const { token } = req.body
    if (token) {
        const fs = require('fs')
        const path = `./credentials/${token}`
        if (fs.existsSync(path)) {

            sock.remove(token);
            connectWaBeforeSend(token).then(status => {
                if (status) {
                    return res.status(200).json({ status: true, message: 'Connection restored' })
                } else {
                    return res.status(200).json({ status: false, message: 'Connection failed' })
                }
            })
            return;
        }
        return res.send({ status: false, message: `${token} Connection failed,please scan first` })
    }
    return res.send({ status: false, message: 'Wrong Parameterss' })


}

module.exports = {

    connectToWhatsApp,
    sendText,
    sendMedia,
    sendButtonMessage,
    sendTemplateMessage,
    sendListMessage,
    isExist,
    fetchGroups,
    deleteCredentials,
    sendMessage,
    initialize,
    connectWaBeforeSend



}