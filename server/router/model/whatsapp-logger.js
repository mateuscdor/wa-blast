const ucFirst = function(str){
    return str?.split(' ').map(s => (s[0]?.toUpperCase() ?? '') + s.substring(1).toLowerCase()).join(' ');
}

const WhatsappLogger = function(){
    const init = function(socket, token){

        try {
            socket.ev.on('connection.update', function(event){

                try {
                    const {connection, lastDisconnect, qr, isOnline, receivedPendingNotifications, isNewLogin} = event;
                    let eventType = qr? 'Generated QR': ucFirst(connection? 'Connection (' + connection + ')': connection);

                    if(!eventType){
                        if(isOnline){
                            eventType = 'Online (' + (isOnline? 'true': 'false') + ')';
                        } else if(receivedPendingNotifications){
                            eventType = 'Received Pending Notifications';
                        } else if(isNewLogin) {
                            eventType = 'New Login';
                        }
                    } else if(connection === 'close'){
                        log.error(lastDisconnect);
                    }

                    log.info(`Event Type (${token}): ${eventType}`);
                } catch (e){
                    console.log('BAD THINGS HAPPENS')
                }

            });
            socket.ev.on('creds.update', function(event){
                log.info(`Event Type (${token}): Credential Updated`);
            });
            socket.ev.on('messaging-history.set', function(event){
                log.info(`Event Type (${token}): Messaging History Updates... (Disabled)`);
            })
            socket.ev.on('messages.update', function(event){
                log.info(`Event Type (${token}): Message Update`);
            });
            socket.ev.on('messages.upsert', function(event){
                log.info(`Event Type (${token}): ${event.messages?.length} Messages Received`);
            });
        } catch (e){
            console.log(e);
        }

    };
    return {
        init,
    };
}

module.exports = WhatsappLogger;
