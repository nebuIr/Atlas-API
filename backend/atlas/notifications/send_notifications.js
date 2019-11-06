require('dotenv').config();
console.log("===== Script started =====");
const news = require("./news_notification.js");
const releases = require("./release_notification.js");
var admin = require("firebase-admin");

var serviceAccount = require("./firebase-adminsdk");

admin.initializeApp({
    credential: admin.credential.cert(serviceAccount),
    databaseURL: process.env.GOOLE_HOST
});

news.data.run();
releases.data.run();

setTimeout(function () {
    console.log("===== Script Ended =====");
    return process.exit(0);
}, 60000);