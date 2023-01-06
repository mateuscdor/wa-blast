const ucFirst = function(str){
    return str?.split(' ').map(s => (s[0]?.toUpperCase() ?? '') + s.substring(1).toLowerCase()).join(' ');
}

const WhatsappLogger = function(){
    const init = function(socket, token){

        socket.ev.on('connection.update', function(event){

            const {connection, lastDisconnect, qr, isOnline, receivedPendingNotifications} = event;
            let eventType = qr? 'Generated QR': ucFirst(connection? 'Connection (' + connection + ')': connection);

            if(!eventType){
                if(isOnline){
                    eventType = 'Online (' + (isOnline? 'true': 'false') + ')';
                }
                if(receivedPendingNotifications){
                    eventType = 'Received Pending Notifications';
                }
            } else if(connection === 'close'){
                log.error(lastDisconnect);
            }

            log.info(`Event Type (${token}): ${eventType}`);

        });

        socket.ev.on('creds.update', function(event){
            log.info(`Event Type (${token}): Credential Updated`);
        });

        socket.ev.on('messaging-history.set', function(event){
            log.info(`Event Type (${token}): Messaging History`);
        })

        socket.ev.on('messages.upsert', function(event){
            log.info(`Event Type (${token}): Message Update`);
            log.info('Messages: ' + event.messages?.length);
        });

    };
    return {
        init,
    };
}

module.exports = WhatsappLogger;
