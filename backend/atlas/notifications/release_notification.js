require('dotenv').config();
const mysql = require('mysql');
const fs = require('fs');
const send = require("./send.js");
const filename = process.env.NOTIFICATION_PATH + '/release_notification.json';
const file_content = fs.readFileSync(filename);
const content = JSON.parse(file_content);
const oldReleasesCount = content.releasesCount;
let newReleasesCount = '';

const pool = mysql.createPool({
    connectionLimit: 10,
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME
});

let methods = {
    run: function () {
        main();
    }
};

function main() {
    getNewCount(function(result){
        newReleasesCount = result;
        //console.log("Variable newReleasesCount result: " + newReleasesCount);
        checkCount(newReleasesCount)
    });
}

function getNewCount(callback) {
    pool.getConnection(function (err, connection) {
        const sqlCount = "SELECT COUNT(*) AS releasesCount FROM releases";
        connection.query(sqlCount, function(err, rows, fields){
            connection.release();
            if (err){throw err;}
            //console.log("SQL newReleasesCount result: " + rows[0].releasesCount); // good
            newReleasesCount = rows[0].releasesCount;  // Scope is larger than function

            return callback(rows[0].releasesCount);
        });
    });
}

function getLatestReleases(callback) {
    pool.getConnection(function (err, connection) {
        const sqlLatest = "SELECT * FROM releases ORDER BY id DESC LIMIT 1";
        connection.query(sqlLatest, function (err, rows) {
            connection.release();
            if (err) throw err;

            let releasesId = rows[0].id;
            let releasesUrl = rows[0].url;
            let releasesTitle = rows[0].title;
            let releasesTimestamp = null;
            let releasesExcerpt = rows[0].excerpt;
            let releasesImage = rows[0].image;
            let releasesBody = rows[0].body;

            return callback(releasesId, releasesUrl, releasesTitle, releasesTimestamp, releasesExcerpt, releasesImage, releasesBody);
        });
    });
}

function checkCount(newReleasesCount) {
    if (oldReleasesCount < newReleasesCount) {
        //console.log("oldReleasesCount: " + oldReleasesCount + " < newReleasesCount: " + newReleasesCount + " = " + true);
        getLatestReleases(function(releasesId, releasesUrl, releasesTitle, releasesTimestamp, releasesExcerpt, releasesImage, releasesBody){
            //console.log("===== RESULT LATEST NEWS =====\n===== " + releasesId + "\n===== " + releasesUrl + "\n===== " + releasesTitle + "\n===== " + releasesTimestamp + "\n===== " + releasesExcerpt + "\n===== " + releasesImage + "\n===== " + releasesBody + "\n===== RESULT LATEST NEWS =====");
            let releases = "Releases";
            send.data.send(releases, releasesId, releasesUrl, releasesTitle, releasesTimestamp, releasesExcerpt, releasesImage, releasesBody);
            content.releasesCount = newReleasesCount;
            fs.writeFileSync(filename, JSON.stringify(content));
        });

    } else {
        //console.log("oldReleasesCount: " + oldReleasesCount + " < newReleasesCount: " + newReleasesCount + " = " + false);
        console.log("===== Script Ended - No notifications sent =====");
    }
}

exports.data = methods;
