const mysql2 = require('mysql2');
require('dotenv').config()
// Create the connection pool. The pool-specific settings are the defaults
const db = mysql2.createPool({
    host: 'localhost',
    user: process.env.DB_USERNAME,
    database: process.env.DB_DATABASE,
    password: process.env.DB_PASSWORD,
    port: process.env.DB_PORT,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});



const setStatus = (device, status) => {
    try {
        db.query(`UPDATE numbers SET status = '${status}' WHERE body = ${device} `)
        return true;

    } catch (error) {
        return false
    }
}

function dbQuery(...query) {
    return new Promise((data, reject) => {
        db.query(...query, (err, res) => {
            if (err) return reject(err);
            try {
                data(res);
            } catch (error) {
                data({});
                //throw error;
            }
        })
    })
}

function dbUpdateQuery(...query){
    return new Promise((data, reject) => {
       db.execute(...query, (err, res) => {
           if (err) return reject(err);
           try {
               data(res);
           } catch (error) {
               data({});
               //throw error;
           }
       })
    });
}

function toQueryTimestamp(timestamp){
    return (new Date(timestamp)).toISOString().replace('T', ' ').replace('\.000Z', '');
}


module.exports = { setStatus, dbQuery, db, dbUpdateQuery, toQueryTimestamp}

// EXPORT
