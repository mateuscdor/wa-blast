const {setStatus} = require("../../database/index");
const {getNumberFromSocket} = require("../helper");
const {DisconnectReason, fetchLatestBaileysVersion} = require("@adiwajshing/baileys");
const QRCode = require("qrcode");

function getProfileImage({sock, token, number}) {
    return new Promise((resolve, reject) => {
        sock.get(token).profilePictureUrl(number).then(r => {
            resolve(r)
        }).catch(e => {
            resolve('https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png')
        });
    });
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

const onConnectionOpen = async function ({sock, io, token, qr}) {

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

const onConnectionClose = function({lastDisconnect, io, sock, token, clearConnection, qr}){

    const number = getNumberFromSocket({sock, token});

    if ((lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut) {
        qr.remove(token)
        if (io != null) io.emit('message', { token: token, message: "Connecting.." })
        if ((lastDisconnect.error)?.output?.payload?.message === 'QR refs attempts ended') {
            sock.get(token).ws.close()
            if (io != null) io.emit('reload-qr', { token: token, message: 'Request QR ended. reload scan to request QR again' })
        }
    } else {
        if(token !== number){
            setStatus(token, 'Disconnect')
            if (io !== null) {
                io.emit('message', { token, message: 'Connection closed. You are logged out.' })
            }
            clearConnection(token)
        }
    }
}

const onQRConnection = function({io, token, qrCode, qr}){
    QRCode.toDataURL(qrCode, function (err, url) {
        if (err) {
            console.log(err);
            return;
        }
        qr.set(token, url);
        if (io !== null) {
            io.emit('qrcode', { token, data: url, message: 'Please scan with your Whatsapp Account' })
        }
    })
}

module.exports = {
    onConnectionStart: onConnectionStart,
    onConnectionOpen: onConnectionOpen,
    onConnectionClose: onConnectionClose,
    onQRConnection: onQRConnection,
}