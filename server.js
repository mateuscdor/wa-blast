'use strict'

const fs = require('fs')
const wa = require('./server/router/model/whatsapp')
const ObjectManager = require('./server/router/model/object-manager');
const cron = require('node-cron');

require('dotenv').config()
const lib = require('./server/lib')
global.log = lib.log
global.sock = ObjectManager();

/**
 * EXPRESS FOR ROUTING
 */
const express = require('express')
const app = express()
// const https = require('https')
const http = require('http')
const server = http.createServer(app)
//usr/local/directadmin/data/users/admin/domains/bot.whatsappmarketting.com.key
// const server = https.createServer({

// "key": fs.readFileSync("/usr/local/directadmin/data/users/admin/domains/bot.whatsappmarketting.com.key"),
//
// "cert": fs.readFileSync("/usr/local/directadmin/data/users/admin/domains/bot.whatsappmarketting.com.cert")
// }, app);

/**
 * SOCKET.IO
 */
const {Server} = require('socket.io');
const io = new Server(server)
const port = process.env.PORT_NODE
// const io = require('socket.io')(server, {
//     cors: {
//         origin: process.env.ORIGIN
//     }
// });
// middleware
app.use((req, res, next) => {
    res.set('Cache-Control', 'no-store')
    req.io = io
    // res.set('Cache-Control', 'no-store')
    next()
})


/**
 * PARSER
 */
// body parser
const bodyParser = require('body-parser')
const {init} = require("./server/router/model/whatsapp");
const Scheduler = require("./server/index")();
// parse application/x-www-form-urlencoded

app.use(bodyParser.urlencoded({ extended: false,limit: '50mb',parameterLimit: 100000 }))
// parse application/json
app.use(bodyParser.json())

app.use(express.static('src/public'));
app.use(require('./server/router'))


// console.log(process.argv)

io.on('connection', (socket) => {
  socket.on('StartConnection', (data) => {
      wa.connectToWhatsApp(data,io).catch(e => {});
  })
    socket.on('LogoutDevice', (device) => {
        wa.deleteCredentials(device,io).catch(e => {});
    })
})
server.listen(port, log.info(`Server run and listening port: ${port}`))
process.on('unhandledRejection', (reason, p) => {
    console.log('Unhandled Rejection at: Promise', p, 'reason:', reason);
    // application specific logging, throwing an error, or other logic here
});
init();
Scheduler.init();
// console.log(Object.keys(server))
