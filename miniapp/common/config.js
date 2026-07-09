console.log(`环境:${process.env.NODE_ENV}`);

let domain;
let environment = process.env.NODE_ENV;
console.log(environment);
if (environment === "development") {
	//开发域名
	// domain = 'http://four.fun.com';
	// domain = 'https://manage.4funinnovate.com';
	// domain = 'https://yunce.jiumirw.com';
	// domain = 'http://115.190.245.200';
	// domain = "https://www.jfyuntu.com";
	// domain = "http://api.mia-233.cn";
	// domain = "http://115.190.245.200";
	domain = "https://api.jfyuntu.com";
} else {
	//发行域名
	// domain = 'https://yunce.jiumirw.com';
	// domain = "https://photo.jfc114.com";
	// domain = "http://api.mia-233.cn";
	// domain = "http://115.190.245.200";
	domain = "https://api.jfyuntu.com";
	// domain = 'https://manage.4funinnovate.com';
}
const config = {
	domain,
	host: domain + "/api",
	environment, //环境
	openLoading: true, //是否开启loading
	features: {
		selectionList: true,
	},
};

export default config;
