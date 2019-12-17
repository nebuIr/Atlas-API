require('dotenv').config();
console.log("===== Script started (news) =====");
const news = require("./news_notification.js");
let admin = require("firebase-admin");

let serviceAccount = require("./firebase-adminsdk");

admin.initializeApp({
    credential: admin.credential.cert(serviceAccount),
    databaseURL: process.env.GOOLE_HOST
});

news.data.run();

setTimeout(function () {
    console.log("===== [NEWS] Script Ended automatically after 60s =====");
    return process.exit(0);
}, 60000);