let exec = require('child_process').exec;

let scheduleRun = function () {
    exec('php bin/import.php import all', function (error, stdOut, stdErr) {
        console.log(error, stdOut, stdErr)
    });
}

let CronJob = require('cron').CronJob;
new CronJob(
    '0,30 * * * *',
    scheduleRun,
    null,
    true,
    'Europe/Berlin'
);
