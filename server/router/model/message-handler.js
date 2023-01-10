const {WAMessageStatus} = require('@adiwajshing/baileys')
const {dbQuery, dbUpdateQuery} = require("../../database");

const getMessageFromDB = async function (messageId) {
    return (await dbQuery(`SELECT * FROM chats WHERE message_id = "${messageId}" LIMIT 1`))[0] ?? null;
};

const UpdateMessageFromDB = function(messageId, updates){

    if(!updates || Object.values(updates).length === 0){
        return;
    }
    let queries = [];
    for(let i in updates) {
        queries.push(`${i} = "${updates[i]}"`);
    }
    queries = queries.join(' ');

    dbUpdateQuery(`UPDATE chats SET ${queries} WHERE message_id = "${messageId}"`).then(r => {

    }).catch(e=>{});
}

const MessageHandler = function(){

    const init = function(socket, token){

        socket.ev.on('messages.update', function(event){
            for(let message of event){
                if(message.key.removeJid?.split('@')[0] === token){

                    getMessageFromDB(message.key.id).then(message => {

                        if(!message){
                            return;
                        }

                        let lastStatus = message.status;
                        let status = message.update.status;

                        if(message.key.fromMe){
                            lastStatus = {
                                [WAMessageStatus.PENDING]: 'PENDING',
                                [WAMessageStatus.DELIVERY_ACK]: 'DELIVERED',
                                [WAMessageStatus.ERROR]: 'ERROR',
                                [WAMessageStatus.READ]: 'READ',
                            }[status] ?? lastStatus;
                        }

                        UpdateMessageFromDB(message.key.id, {
                            read_status: lastStatus
                        });

                    }).catch(e => {});
                }
            }

        });


    }

    return {
        init
    }
}

module.exports = MessageHandler;