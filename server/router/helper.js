let mime = require('mime-types');
const fs = require("fs");

function formatReceipt(receipt) {
    try {
        if (receipt.endsWith('@g.us')) {
            return receipt
        }
        let formatted = receipt.replace(/\D/g, '');

        if (formatted.startsWith('0')) {
            formatted = '62' + formatted.substr(1);
        }

        if (!formatted.endsWith('@c.us')) {
            formatted += '@c.us';
        }

        return formatted;
    } catch (error) {
        console.log(error)
    }

    // }
}
async function asyncForEach(array, callback) {
    for (let index = 0; index < array.length; index++) {
        await callback(array[index], index, array);
    }
}

const getNumberFromSocket = function({sock, token}){
    return sock.get(token)?.user?.id.split(':')[0];
}

const convertFileTypes = function(message){

    if('image' in message){
        let file = message.image.url;
        let caption = message.caption ?? message.text ?? '';
        let lookup = mime.lookup(file);
        let extendedType = {};

        let imageMimes = [
            'image/jpeg',
            'image/png',
            'image/svg',
            'image/svg+xml',
        ];
        let audioExtensions = [
            'audio/mpeg',
            'audio/ogg',
            'audio/wav',
            'audio/webm',
        ];
        let videoExtensions = [
            'video/mpeg',
            'video/ogg',
            'video/webm'
        ];
        let excelExtensions = [
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        let type = 'text';

        if(imageMimes.includes(lookup)){
            type = 'image'
        }
        if(videoExtensions.includes(lookup)){
            type = 'video'
        }
        if(audioExtensions.includes(lookup)){
            type = 'audio'
        }
        if(lookup === 'application/pdf'){
            type = 'pdf'
        }
        if(excelExtensions.includes(lookup)){
            type = 'xlsx';
        }
        if(lookup === 'application/excel'){
            type = 'xls';
        }
        if(lookup === 'application/zip'){
            type = 'zip';
        }
        if(lookup === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'){
            type = 'docx';
        }
        if(lookup === 'application/msword'){
            type = 'doc';
        }

        let url = file;
        try {
            if (type === 'image') {
                extendedType = { image: { url } , caption: caption ? caption : null };
            } else if (type === 'video') {
               extendedType = { video: { url }, caption: caption ? caption : null };
            } else if (type === 'audio') {
                extendedType = { audio: { url } , caption: caption ? caption : null };
            } else {
                extendedType = {document: {url: url}, mimetype: lookup};
            }
            // console.log(sendMsg)
            delete message.image;
            return {
                ...message,
                ...extendedType
            };
        } catch (error) {
            console.log(error)
            return message;
        }
    }


}

module.exports = {
    formatReceipt,
    getNumberFromSocket,
    asyncForEach,
    convertFileTypes
}