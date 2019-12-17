require('dotenv').config();
console.log("===== Script started (releases) =====");
const releases = require("./release_notification.js");
let admin = require("firebase-admin");

let serviceAccount = require("./firebase-adminsdk");

admin.initializeApp({
    credential: admin.credential.cert(serviceAccount),
    databaseURL: process.env.GOOLE_HOST
});

releases.data.run();

setTimeout(function () {
    console.log("===== [RELEASES] Script Ended automatically after 60s =====");
    return process.exit(0);
}, 60000);