require('dotenv').config();
console.log("===== Script started (releases) =====");
const releases = require("./release_notification.js");
var admin = require("firebase-admin");

var serviceAccount = require("./firebase-adminsdk");

admin.initializeApp({
    credential: admin.credential.cert(serviceAccount),
    databaseURL: process.env.GOOLE_HOST
});

releases.data.run();

setTimeout(function () {
    console.log("===== Script Ended (releases) =====");
    return process.exit(0);
}, 60000);