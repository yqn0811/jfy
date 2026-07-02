var ASCII = {
	sort_ascii(obj) {
		let arr = new Array();
		let num = 0;
		for (let i in obj) {
			arr[num] = i;
			num++;
		}
		let sortArr = arr.sort();
		let str = ''; //自定义排序字符串
		for (let i in sortArr) {
			// if (undefined != obj[sortArr[i]] && "" != obj[sortArr[i]]) {
				str += sortArr[i] + '=' + obj[sortArr[i]] + '&';
			// }
		}
		//去除两侧字符串
		let char = '&';
		str = str.replace(new RegExp('^\\' + char + '+|\\' + char + '+$', 'g'), '');
		return str;
	}
};

//导出
export default ASCII