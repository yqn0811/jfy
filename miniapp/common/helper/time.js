/**
 * @param {Date()} date  
 */
function getCurrentTimeStamp(date = '') {
	let _date = date || new Date()
	return parseInt(_date.getTime() / 1000);
}

/**
 * 时分秒转化为秒
 */
function changeToSec(time) {
	var hour = time.split(':')[0];
	var min = time.split(':')[1];
	var sec = time.split(':')[2];

	let s = Number(hour * 3600) + Number(min * 60) + Number(sec);
	return parseInt(s)
}

function getNowDate(){
	// 获取当前日期
	let currentDate = new Date();
	 let year = currentDate.getFullYear();
	let month = currentDate.getMonth() + 1;
	let day = currentDate.getDate();
	 
	let date = `${year}-${month<10?'0'+month:month}-${day<10?'0'+day:day}`
	return date
}

export {
	getCurrentTimeStamp,
	changeToSec,
	getNowDate
};
