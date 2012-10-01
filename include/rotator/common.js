Date.prototype.monthNames = [
    "January", "February", "March",
    "April", "May", "June",
    "July", "August", "September",
    "October", "November", "December"
];

Date.prototype.getMonthName = function() {
    return this.monthNames[this.getMonth()];
};

Date.prototype.get_current_datetime = function() {
    var dt = new Date();
    return dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
    return dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate + ' '
        + dt.getHours() + ':' + dt.getMinutes() + ':' + dt.getSeconds();
};

Date.prototype.getShortMonthName = function () {
    return this.getMonthName().substr(0, 3);
};

