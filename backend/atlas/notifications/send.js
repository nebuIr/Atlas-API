let methods = {
    send: function (topic, id, url, title, timestamp, excerpt, image, body) {
        let admin = require("firebase-admin");

        //console.log("===== SEND =====\n===== " + id + "\n===== " + url + "\n===== " + title + "\n===== " + timestamp + "\n===== " + excerpt + "\n===== " + image + "\n===== " + body + "\n===== SEND =====");

        let message = {
            notification: {
                title: title,
                body: excerpt,
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

        let options = {
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