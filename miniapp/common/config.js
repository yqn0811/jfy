const environment = process.env.NODE_ENV || "development";
const apiEnvironment = process.env.VUE_APP_API_ENV || environment;
const TEST_API_DOMAIN = "https://api-test.jfyuntu.com";
const PROD_API_DOMAIN = "https://api.jfyuntu.com";
const domain = apiEnvironment === "production" ? PROD_API_DOMAIN : TEST_API_DOMAIN;

const config = {
	domain,
	host: domain + "/api",
	environment, //运行环境
	apiEnvironment, //接口环境
	openLoading: true, //是否开启loading
	features: {
		selectionList: true,
	},
};

export default config;
