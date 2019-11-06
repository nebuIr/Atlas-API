var methods = {
send: function(topic, id, url, title, timestamp, date, teaser, image, content) {
        var admin = require("firebase-admin");

        //console.log("===== SEND =====\n===== " + id + "\n===== " + url + "\n===== " + title + "\n===== " + timestamp + "\n===== " + date + "\n===== " + teaser + "\n===== " + image + "\n===== " + content + "\n===== SEND =====");

        var message = {
            notification: {
                title: title,
                body: teaser,
                image: image
            },
            android: {
                priority: "normal",
                ttl: 3600 * 1000
            },
            data: {
                id: id.toString(),
                category: topic,
                image: image
            },
            topic: topic.toLowerCase()
        };

        var options = {
            priority: "normal",
            timeToLive: 60 * 60 * 24
        };

        admin.messaging().send(message)
            .then(function (response) {
                console.log("===== Successfully sent message =====", response);
            })
            .catch(function (error) {
                console.log("===== Error sending message =====", error);
            });
    }
};

exports.data = methods;