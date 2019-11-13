require('dotenv').config();
const mysql = require('mysql');
const fs = require('fs');
const send = require("./send.js");
const filename = './news_notification.json';
const file_content = fs.readFileSync(filename);
const content = JSON.parse(file_content);
const oldNewsCount = content.newsCount;
let newNewsCount = '';

const pool = mysql.createPool({
    connectionLimit: 10,
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME
});

var methods = {
    run: function () {
        main();
    }
};

function main() {
    getNewCount(function(result){
        newNewsCount = result;
        //console.log("Variable newNewsCount result: " + newNewsCount);
        checkCount(newNewsCount)
    });
}

function getNewCount(callback) {
    pool.getConnection(function (err, connection) {
        const sqlCount = "SELECT COUNT(*) AS newsCount FROM news";
        connection.query(sqlCount, function(err, rows, fields){
            connection.release();
            if (err){throw err;}
            //console.log("SQL newNewsCount result: " + rows[0].newsCount); // good
            newNewsCount = rows[0].newsCount;  // Scope is larger than function

            return callback(rows[0].newsCount);
        });
    });
}

function getLatestNews(callback) {
    pool.getConnection(function (err, connection) {
        const sqlLatest = "SELECT * FROM news ORDER BY id DESC LIMIT 1";
        connection.query(sqlLatest, function (err, rows) {
            connection.release();
            if (err) throw err;

            let newsId = rows[0].id;
            let newsUrl = rows[0].url;
            let newsTitle = rows[0].title;
            let newsTimestamp = rows[0].timestamp;
            let newsExcerpt = rows[0].excerpt;
            let newsImage = rows[0].image;
            let newsBody = rows[0].body;

            return callback(newsId, newsUrl, newsTitle, newsTimestamp, newsExcerpt, newsImage, newsBody);
        });
    });
}

function checkCount(newNewsCount) {
    if (oldNewsCount < newNewsCount) {
        //console.log("oldNewsCount: " + oldNewsCount + " < newNewsCount: " + newNewsCount + " = " + true);
        getLatestNews(function(newsId, newsUrl, newsTitle, newsTimestamp, newsExcerpt, newsImage, newsBody){
            //console.log("===== RESULT LATEST NEWS =====\n===== " + newsId + "\n===== " + newsUrl + "\n===== " + newsTitle + "\n===== " + newsTimestamp + "\n===== " + newsExcerpt + "\n===== " + newsImage + "\n===== " + newsBody + "\n===== RESULT LATEST NEWS =====");
            var news = "News";
            send.data.send(news, newsId, newsUrl, newsTitle, newsTimestamp, newsExcerpt, newsImage, newsBody);
            content.newsCount = newNewsCount;
            fs.writeFileSync(filename, JSON.stringify(content));
        });

    } else {
        //console.log("oldNewsCount: " + oldNewsCount + " < newNewsCount: " + newNewsCount + " = " + false);
        console.log("===== Script Ended - No notifications sent =====");
    }
}

exports.data = methods;