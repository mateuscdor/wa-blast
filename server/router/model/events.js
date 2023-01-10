const {setStatus} = require("../../database/index");
const {getNumberFromSocket} = require("../helper");
const {DisconnectReason, fetchLatestBaileysVersion} = require("@adiwajshing/baileys");
const QRCode = require("qrcode");

async function getProfileImage({sock, token, number}) {
    try {
        return await sock.get(token).profilePictureUrl(number, 'image', 10000);
    } catch (e) {
        return "https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png";
    }
}

const onConnectionStart = async function ({sock, io, token}) {

    try {
        let newToken = getNumberFromSocket({sock, token});
        let number = newToken + '@s.whatsapp.net';
        const ppUrl = await getProfileImage({sock, token, number});

        if (io !== null) {
            let newToken = sock.get(token).user.id.split(':')[0];
            io.emit('connection-open', {token: newToken, user: sock.get(token).user, ppUrl});
            if (newToken !== token && !newToken) {
                io.emit('message', {token, message: "Your whatsapp number is not equal to the registered number"});
            }
        }

        return {status: true, message: 'Already connected'};
    } catch (error) {
        if (io !== null) {
            io.emit('message', {token, message: `Connecting..`})
        }
    }
};

const onConnectionOpen = async function ({io, token, qr}) {

    let number = getNumberFromSocket({sock, token});

    let destination = number + '@s.whatsapp.net';
    if (token === number) {
        setStatus(token, 'Connected');
    } else {
        io.emit('reload-qr', {token, message: "Your whatsapp number is not equal to the registered number"});
        return;
    }

    const ppUrl = await getProfileImage({sock, token, number: destination});
    if (io !== null) {
        io.emit('connection-open', {token, user: sock.get(token).user, ppUrl});
    }

    qr.remove(token);
};

const onConnectionClose = async function ({lastDisconnect, io, token, clearConnection, loop, qr}) {

    try {
        let statusCode = lastDisconnect?.error?.output?.payload?.statusCode;
        if ((statusCode !== DisconnectReason.loggedOut)) {
            qr.remove(token)
            if (statusCode === DisconnectReason.restartRequired) {
                if (io != null) io.emit('reload-qr', {
                    token: token,
                    message: 'Request QR ended. reload scan to request QR again'
                })
            } else {
                if (io != null) io.emit('message', {token: token, message: "Connecting.."})
            }
            if (statusCode === 'QR refs attempts ended') {
                sock.get(token).ws.close()
                if (io != null) io.emit('reload-qr', {
                    token: token,
                    message: 'Request QR ended. reload scan to request QR again'
                })
            }
            if ([DisconnectReason.connectionLost].includes(statusCode)) {
                if(qr && !io){
                    sock.get(token)?.ws.close()
                    return;
                }
                sock.get(token)?.ws.close()
                log.info('Starting a new connection...');
                return await loop();
            }
        } else {
            if (io !== null) {
                io.emit('reload-qr', {token, message: 'Connection closed. You are logged out.'})
            }
            clearConnection(token)
        }
    } catch (e){
        console.log("CONNECTION CLOSE ", e);
    }
}

const onQRConnection = function({io, token, qrCode, qr}){
    if(io){
        QRCode.toDataURL(qrCode).then(function (url) {
            qr.set(token, url);
            if (io !== null) {
                io.emit('qrcode', { token, data: url, message: 'Please scan with your Whatsapp Account' })
            }
        }).catch(e => {
            log.error('QR Error');
        });
    } else {
        let socket = sock.get(token);
        sock.remove(token);
        socket.ws.close();
    }
}

module.exports = {
    onConnectionStart: onConnectionStart,
    onConnectionOpen: onConnectionOpen,
    onConnectionClose: onConnectionClose,
    onQRConnection: onQRConnection,
}