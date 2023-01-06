'use strict'

const wa = require('./whatsapp')
const lib = require('../../lib')
const { dbQuery, dbUpdateQuery, toQueryTimestamp} = require('../../database')
const { asyncForEach, formatReceipt } = require('../helper')
const createInstance = async (req, res) => {

    const { token } = req.body
    if (token) {
        try {
            const connect = await wa.connectToWhatsApp(token, req.io)
            const status = connect?.status
            const message = connect?.message
            return res.send({
                status: status ?? 'processing',
                qrcode: connect?.qrcode,
                message: message ? message : 'Processing'
            })
        } catch (error) {
            console.log(error)
            return res.send({ status: false, error: error })
        }
    }
    res.status(403).end('Token needed')

}



const sendText = async (req, res) => {
    const { token, number, text } = req.body
    if (token && number && text) {
        let check = await wa.isExist(token, formatReceipt(number));
        if (!check) return res.send({ status: false, message: 'The destination Number not registered in whatsapp or your sender not connected' })
        const sendingTextMessage = await wa.sendText(token, number, text)
        if (sendingTextMessage) {
            return res.send({ status: true, data: sendingTextMessage })
        }
        return res.send({ status: false, message: 'Check your whatsapp connection' })
    }
    res.send({ status: false, message: 'Check your parameter' })

}

const sendMedia = async (req, res) => {

    const { token, number, type, url, fileName, caption } = req.body

    if (token && number && type && url && caption) {
        let check = await wa.isExist(token, formatReceipt(number));
        if (!check) return res.send({ status: false, message: 'The destination Number not registered in whatsapp or your sender not connected' })
        const sendingMediaMessage = await wa.sendMedia(token, number, type, url, fileName, caption)
        if (sendingMediaMessage) return res.send({ status: true, data: sendingMediaMessage })
        return res.send({ status: false, message: 'Check your connection' })
    }
    res.send({ status: false, message: 'Check your parameter' })

}

const sendButtonMessage = async (req, res) => {

    const { token, number, button, message, footer, image } = req.body

    const buttons = JSON.parse(button);
    if (token && number && button && message && footer) {
        let check = await wa.isExist(token, formatReceipt(number));
        if (!check) return res.send({ status: false, message: 'The destination Number not registered in whatsapp or your sender not connected' })
        const sendButtonMessage = await wa.sendButtonMessage(token, number, buttons, message, footer, image)
        if (sendButtonMessage) return res.send({ status: true, data: sendButtonMessage })
        return res.send({ status: false, message: 'Check your connection' })
    }
    res.send({ status: false, message: 'Check your parameterr' })

}

const sendTemplateMessage = async (req, res) => {

    const { token, number, button, text, footer, image } = req.body

    if (token && number && button && text && footer) {
        let check = await wa.isExist(token, formatReceipt(number));
        if (!check) return res.send({ status: false, message: 'The destination Number not registered in whatsapp or your sender not connected' })

        const sendTemplateMessage = await wa.sendTemplateMessage(token, number, JSON.parse(button), text, footer, image)
        if (sendTemplateMessage) return res.send({ status: true, data: sendTemplateMessage })
        return res.send({ status: false, message: 'Check your connection' })
    }
    res.send({ status: false, message: 'Check your parameter' })

}

const sendListMessage = async (req, res) => {

    const { token, number, list, text, footer, title, buttonText } = req.body

    if (token && number && list && text && footer && title && buttonText) {
        try {
            let check = await wa.isExist(token, formatReceipt(number));
            if (!check) return res.send({ status: false, message: 'The destination Number not registered in whatsapp or your sender not connected' })

            const sendListMessage = await wa.sendListMessage(token, number, JSON.parse(list), text, footer, title, buttonText)
            if (sendListMessage) return res.send({ status: true, data: sendListMessage })
            return res.send({ status: false, message: 'Check your connection' })
        } catch (e){
            return res.send({status: false, message: e});
        }
    }
    res.send({ status: false, message: 'Check your parameter' })

}

const fetchGroups = async (req, res) => {

    const { token } = req.body

    if (token) {
        await wa.connectWaBeforeSend(token);
        const fetchGroups = await wa.fetchGroups(token)
        if (fetchGroups) return res.send({ status: true, data: fetchGroups })
        return res.send({ status: false, message: 'Check your connection' })
    }
    res.send({ status: false, message: 'Check your parameter' })

}

const blast = async (req, res) => {
    const dat = req.body.data;
    const data = JSON.parse(dat);
    const delay = req.body.delay ?? 3;


    const check = await wa.isExist(data[0].sender, formatReceipt(data[0].sender));
 

    if (!check) {
        return res.send({ status: false, message: 'Check your whatsapp connection' })
    }
    let successNumber = [];
    let failedNumber = [];
    function waitforme(milisec) {

        return new Promise(resolve => {
            setTimeout(() => { resolve('') }, milisec);
        })
    }
    try {
        await asyncForEach(data, async (item, index) => {
            const { sender, receiver, message, campaign_id } = item;
            console.log(item);
            if (sender && receiver && message) {

                const sendingTextMessage = await wa.sendMessage(sender, receiver, message)

                if (sendingTextMessage) {
                    successNumber.push(receiver);
                } else {
                    failedNumber.push(receiver);
                }
            }

            await waitforme(delay * 1000);
        })
    } catch (e){
        console.error(e);
    }

    return res.send({ status: true, success: successNumber, failed: failedNumber })



}


const deleteCredentials = async (req, res) => {
    const { token } = req.body

    if (token) {
        const deleteCredentials = await wa.deleteCredentials(token)
        if (deleteCredentials) return res.send({ status: true, data: deleteCredentials })
        return res.send({ status: false, message: 'Check your connection' })
    }
    res.send({ status: false, message: 'Check your parameter' })
}

const direct = async function (req, res) {
    const dat = req.body.data;
    const data = JSON.parse(dat);

    const {sender, receiver, message, chat_id: chatId} = data;

    if (sender && receiver && message && chatId) {

        wa.sendMessage(sender, receiver, message).then(messageItem => {
            if(!messageItem?.key){
                return;
            }
            let messageId = messageItem.key.id;
            let timestamp =  parseInt(messageItem.messageTimestamp);

            dbUpdateQuery(`UPDATE chats SET message_id = "${messageId}", read_status = "DELIVERED", sent_at = "${toQueryTimestamp(timestamp * 1000)}" WHERE id = "${chatId}" && message_id IS NULL`);
        }).catch(e => {
            console.log(e);
        });
        if(res){
            return res.send({
                status: true,
                message: "Whatsapp is sending message"
            })
        }
    }
    if(res){
        return res.send({
            status: false,
            message: "Bad Request"
        })
    }
}


module.exports = {

    createInstance,
    sendText,
    sendMedia,
    sendButtonMessage,
    sendTemplateMessage,
    sendListMessage,
    deleteCredentials,
    fetchGroups,
    blast,
    direct,

}