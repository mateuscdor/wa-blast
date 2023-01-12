'use strict'

const { default: makeWASocket, makeWALegacySocket, WAMessageStatus, makeInMemoryStore} = require('@adiwajshing/baileys')
const { fetchLatestBaileysVersion, useMultiFileAuthState, makeCacheableSignalKeyStore } = require('@adiwajshing/baileys')
const WhatsappLogger = require('./whatsapp-logger');
const MessageHandler = require('./message-handler');
const ObjectManager = require("./object-manager");

// const logger = require('../../lib/pino')
const lib = require('../../lib')
const fs = require('fs')
const logManager = new WhatsappLogger();
const messenger = new MessageHandler();
let QR = ObjectManager();
let intervalStore = ObjectManager();
const { setStatus } = require('../../database/index')
const { autoReply, saveLiveChat } = require('./autoreply')
const { formatReceipt } = require('../helper')
const axios = require('axios')

/***********************************************************
 * FUNCTION
 **********************************************************/
const MAIN_LOGGER = require('../../lib/pino')
const request = require("request").defaults({ encoding: null });
const {dbQuery, dbUpdateQuery, toQueryTimestamp, db} = require("../../database");
const {onConnectionOpen, onConnectionClose, onQRConnection, onConnectionStart} = require("./events");
const cron = require("node-cron");

const logger = MAIN_LOGGER.child({})
//  logger.level = 'trace'

const useStore = !process.argv.includes('--no-store')

// external map to store retry counts of messages when decryption/encryption fails
// keep this out of the socket itself, so as to prevent a message decryption/encryption loop across socket restarts
const msgRetryCounterMap = () => MessageRetryMap = {}

const onMessageUpsert = function({sock, token, io}){
    return async function(event){
        try {
            if (!event.messages) {
                return;
            }
            let ids = event.messages.map(m => m?.key?.id).filter(f => !!f);
            let foundIds = (await dbQuery(`SELECT message_id FROM chats WHERE message_id IN (${db.escape(ids)})`)).map(m => m.message_id);
            for (let message of event.messages.filter(m => !foundIds.includes(m.key.id))) {
                saveLiveChat(message, sock.get(token)).catch(e=>{log.error(e)});
                autoReply(message, sock.get(token)).catch(e=>{log.error(e)});
            }
        } catch (e){
            log.error(e);
        }
    }
}
const onCredentialUpdate = function({saveCreds}){
    return async function(){
        try {
            await saveCreds();
        } catch (e){
        }
    }
}

const updateContacts = function({token, contacts}){
    let folder = fs.readdirSync('./whatsapp-storage/');
    let dest = './whatsapp-storage/' + token + '/contacts.json';
    if(!folder.includes(token)){
        fs.mkdirSync('./whatsapp-storage/' + token);
        fs.writeFileSync(dest, '[]');
    }
    let json = JSON.parse(fs.readFileSync(dest).toString('utf8'));
    let foundIds = [];
    json = json.map(j => {
        let found = contacts.find(c => c.id === j.id);
        if(found){
            foundIds.push(found.id);
            return {
                ...j,
                ...found
            };
        }
        return j;
    })
    json.push(...contacts.filter(c => !foundIds.includes(c.id)));
    fs.writeFileSync(dest, JSON.stringify(json));
}

// start a connection
const connectToWhatsApp = async (token, io = null) => {

    if(sock.get(token) && sock.getInfo(token).isOnline){
        return {
            status: true,
            sock: sock.get(token),
            qrcode: sock.getInfo(token).qr,
            message: "Please scan the QR Code"
        }
    }
    log.info('Connection is starting...');
    try {
        let qrCode = QR.get(token);
        if (qrCode) {
            if (io !== null) {
                io.emit('qrcode', { token, data: qrCode, message: "please scan with your Whatsapp Account" })
            }
            return ({
                status: false,
                sock: sock.get(token),
                qrcode: qrCode,
                message: "Please scan the QR Code"
            });
        }
        await onConnectionStart({sock, io, token});

        // fetch latest version of Chrome For Linux

        log.info('Connection initiating 1...');

        const chrome = JSON.parse(fs.readFileSync(__dirname + '/chrome-stable.json').toString('utf8'));
        const { version, isLatest } = await fetchLatestBaileysVersion()

        log.info('You re using whatsapp gateway M Pedia v4.3.2 - Contact admin if any trouble : 082298859671');
        log.info(`using WA v${version.join('.')}, isLatest: ${isLatest}`)

        const {state, saveCreds} = await useMultiFileAuthState(`./credentials/${token}`)
        let socket = makeWASocket({
            version,
            linkPreviewImageThumbnailWidth: 150,
            generateHighQualityLinkPreview: true,
            // browser: ['Linux', 'Chrome', '103.0.5060.114'],
            browser: ['M Pedia', 'Chrome', chrome?.versions[0]?.version],
            patchMessageBeforeSending: (message) => {
                const requiresPatch = !!(
                    message.buttonsMessage
                    // || message.templateMessage
                    || message.listMessage
                );
                if (requiresPatch) {
                    message = {
                        viewOnceMessage: {
                            message: {
                                messageContextInfo: {
                                    deviceListMetadataVersion: 2,
                                    deviceListMetadata: {},
                                },
                                ...message,
                            },
                        },
                    };
                }

                return message;
            },
            printQRInTerminal: false,
            logger,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, logger)
            }
        });
        sock.set(token, socket);
        logManager.init(socket, token);
        messenger.init(socket, token);
        socket.ev.on('connection.update', async function(event){
            const {connection, lastDisconnect, qr} = event;

            try {
                if (connection === 'close') {
                    await onConnectionClose({
                        lastDisconnect, io, sock, token, clearConnection, qr: QR, async loop() {
                            // socket.ev.removeAllListeners('connection.update');
                            await connectToWhatsApp(token, io)
                        }
                    });
                }
                if (qr) {
                    // SEND TO YOUR CLIENT SIDE
                    onQRConnection({io, token, qrCode: qr, qr: QR})
                }
                if (connection === 'open') {
                    await onConnectionOpen({io, token, qr: QR, sock})
                }
            } catch (e){
                log.error(e);
            }
        })
        socket.ev.on('messages.upsert', onMessageUpsert({sock, token, io}))
        socket.ev.on('creds.update', onCredentialUpdate({saveCreds}))
        socket.ev.on('messaging-history.set', function(event){
            updateContacts({token, contacts: event.contacts});
        })
        socket.ev.on('contacts.upsert', function(event){
            updateContacts({token, contacts: event});
        })
        socket.ev.on('contacts.update', function(event){
            updateContacts({token, contacts: event});
        })
        socket.ev.on('contacts.set', function(event){
            updateContacts({token, contacts: event});
        });
        socket.waitForConnectionUpdate((ev) => {

            if(ev.connection === 'close'){
                ev.isOnline = false;
                if(ev.lastDisconnect?.error?.output?.statusCode === 408){
                    setTimeout(()=>{
                        connectToWhatsApp(token).catch(e => {

                        });
                    }, 2000);
                }
            } else {
                ev.lastDisconnect = undefined;
            }
            if(!io && ev.qr){
                return;
            }

            sock.setInfo(token, {
                ...sock.getInfo(token),
                ...ev,
            })


        }).catch(e => {

        });
        return ({
            status: sock.getInfo(token)?.isOnline,
            sock: sock.get(token),
            qrcode: QR.get(token)
        });
    } catch (e) {
        if(e?.code === 'ENOTFOUND'){
            log.error('Network Problem...');
        } else {
            log.error('Make WA Socket Problem');
        }
        console.log(e);
    }
}
//
async function connectWaBeforeSend(token) {
    let status = undefined;

    try {
        let wa = await connectToWhatsApp(token);
        status = wa?.status;
    } catch (e){
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
            // let data = await (new Promise((resolve, reject) => {
            //     request.get(message.image.url, function (error, response, body) {
            //         if (!error && response.statusCode === 200) {
            //             let data = Buffer.from(body,'base64');
            //             resolve(data);
            //         } else {
            //             reject("Image Not found");
            //         }
            //     });
            // }))
            // message.jpegThumbnail = Buffer.from(await sharp(data).jpeg({
            //     quality: 30
            // }).resize({ width: 100 }).toBuffer()).toString('base64');
        }
        if('buttons' in message && !message.buttons.length){
            delete message.buttons;
        }
         // awaiting sending message
        return await sock.get(token).sendMessage(formatReceipt(number), message)

    } catch (error) {
        return false
    }

}

// media
async function sendMedia(token, destination, type, url, fileName, caption) {

    let sendMsg;
    /**
     * type is "url" or "local"
     * if you use local, you must upload into src/public/temp/[fileName]
     */
    const number = formatReceipt(destination);
    try {
        if (type === 'image') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { image: url ? { url } : fs.readFileSync('src/public/temp/' + fileName), caption: caption ? caption : null },
            );
        } else if (type === 'video') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                {
                    video: url ? {url} : fs.readFileSync('src/public/temp/' + fileName),
                    caption: caption ? caption : null
                },
            );
        } else if (type === 'audio') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { audio: url ? { url } : fs.readFileSync('src/public/temp/' + fileName), caption: caption ? caption : null },
            );
        } else if (type === 'pdf') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/pdf' },
                { url: url }
            );
        } else if (type === 'xls') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/excel' },
                { url: url }
            );
        } else if (type === 'xlsx') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
                { url: url }
            );
        } else if (type === 'doc') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/msword' },
                { url: url }
            );
        } else if (type === 'docx') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
                { url: url }
            );
        } else if (type === 'zip') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/zip' },
                { url: url }
            );
        } else if (type === 'mp3') {
            sendMsg = await sock.get(token).sendMessage(
                number,
                { document: { url: url }, mimetype: 'application/mp3' },
                { url: url }
            );
        } else {
            console.log('Please add your own role of mimetype')
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

    let buttonMessage;
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
            buttonMessage = {
                image: type == 'url' ? { url: image } : fs.readFileSync('src/public/temp/' + image),
                // jpegThumbnail: await lib.base64_encode(),
                caption: message,
                footer: footer,
                buttons: buttons,
                headerType: 4
            };
        } else {
            buttonMessage = {
                text: message,
                footer: footer,
                buttons: buttons,
                headerType: 1
            };
        }
        return await sock.get(token).sendMessage(formatReceipt(number), buttonMessage)
    } catch (error) {
        console.log(error)
        return false
    }

}


async function sendTemplateMessage(token, number, button, text, footer, image) {

    let buttonMessage;
    try {
        // const templateButtons = [
        //     { index: 1, urlButton: { displayText: button[0].displayText, url: button[0].url } },
        //     { index: 2, callButton: { displayText: button[1].displayText, phoneNumber: button[1].phoneNumber } },
        //     { index: 3, quickReplyButton: { displayText: button[2].displayText, id: button[2].id } },
        // ]
      

        if (image) {
            buttonMessage = {
                caption: text,
                footer: footer,
                templateButtons: button,
                image: { url: image },
                viewOnce: true
            };
        } else {
            buttonMessage = {
                text: text,
                footer: footer,
                templateButtons: button,
                viewOnce: true
            };
        }

        return await sock.get(token).sendMessage(formatReceipt(number), buttonMessage)
    } catch (error) {
        console.log(error)
        return false
    }

}

// list message
async function sendListMessage(token, number, list, text, footer, title, buttonText) {

    try {

        const listMessage = { text, footer, title, buttonText, sections: [list] }

        return await sock.get(token).sendMessage(formatReceipt(number), listMessage)
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
        return Object.entries(getGroups).slice(0).map(entry => entry[1])
    } catch (error) {
        return false
    }
}

// if exist
async function isExist(token, number) {

    try {
        if(!number){
            number = formatReceipt(token)
        }
        if (!sock.get(token)) {
            const status = await connectWaBeforeSend(token)
            if (!status) {
                return false
            }
        }
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
    try {
        if (io !== null) {
            io.emit('message', { token: token, message: 'Logging out..' })
        }
        if (typeof sock.get(token) === 'undefined') {
            const status = await connectWaBeforeSend(token)
            if (status) {
                sock.get(token)?.logout()
                sock.remove(token)
            }
        } else {
            sock.get(token)?.logout()
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
    log.info('Token Information (' + token + '): Removing Credentials')
    if (fs.existsSync(`./credentials/${token}`)) {
        fs.rmSync(`./credentials/${token}`, { recursive: true, force: true })
        console.log(`credentials/${token} is deleted`);
    }
    if (fs.existsSync(`./whatsapp-storage/${token}`)) {
        fs.rmSync(`./whatsapp-storage/${token}`, { recursive: true, force: true })
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
    const { token } = req.body;
    if (token) {
        const fs = require('fs')
        const path = `./credentials/${token}`
        if (fs.existsSync(path)) {
            connectWaBeforeSend(token).then(status => {
                if (status) {
                    return res.status(200).json({ status: true, message: 'Connection restored' })
                } else {
                    return res.status(200).json({ status: false, message: 'Connection failed' })
                }
            }).catch(e => {
                console.log(e);
            })
            return;
        }
        return res.send({ status: false, message: `${token} Connection failed,please scan first` })
    }
    return res.send({ status: false, message: 'Wrong Parameterss' })


}

const init = function () {

    getChromeLates().then(r => {
       fs.writeFileSync(__dirname + '/chrome-stable.json', JSON.stringify(r.data));
    }).catch(e => {
        console.error('Chrome Error' + e);
    });

    dbQuery('SELECT * FROM numbers').then(numbers => {
        numbers = numbers.filter(n => fs.readdirSync('./credentials/' + formatReceipt(n.body).split('@')[0])?.length);
        for (let {body} of numbers) {
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

                }).catch(e=>{});
            }).catch(e=>{});
        }
    }).catch(e => {});
}



module.exports = {

    init,
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
    connectWaBeforeSend,


}