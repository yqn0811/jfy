import ASCII from "./ASCII";
import md5 from "./md5.js"
import Cache from './cache.js';
const cache = new Cache();

export const warnRelaunch = (url = '/pages/index/index', title = '异常访问!') => {
	uni.showModal({
		showCancel: false,
		title: '提示',
		content: title,
		success: () => {
			reLaunch(url);
		}
	});
};

export const showModal = (title = '发生异常了!', config = {
	showCancel: false
}) => {
	return new Promise((resolve) => {
		uni.showModal({
			showCancel: config.showCancel,
			title: '提示',
			content: title,
			success: (res) => {
				return resolve(res.confirm);
			}
		});
	});
};

export const switchTab = (url) => {
	uni.switchTab({
		url
	});
};


export const reLaunch = (url = '/pages/index/index') => {
	uni.reLaunch({
		url
	});
};

export const navigateTo = (url) => {
	if (getCurrentPages().length > 7) {
		uni.reLaunch({
			url
		});
		
	} else {
		uni.navigateTo({
			url,
			animationType:"none",
			animationDuration:0
		});
	}

};


export const navigateBack = () => {
	uni.navigateBack({});
};

export const redirectTo = (url) => {
	uni.redirectTo({
		url,
	});
};

export const showToast = (title, position = 'center', duration = 1500,icon = 'none') => {
	uni.showToast({
		title,
		position,
		duration,
		icon,
	});
};


export const showLoading = (title = '', mask = true) => {
	uni.showLoading({
		title: title || '数据加载中',
		mask: mask
	});
};
export const hideLoading = (title = '', mask = true) => {
		uni.hideLoading();
};

export const getSystemInfoCompat = () => {
	const windowInfo = typeof uni.getWindowInfo === 'function' ? uni.getWindowInfo() : {};
	const deviceInfo = typeof uni.getDeviceInfo === 'function' ? uni.getDeviceInfo() : {};
	const appBaseInfo = typeof uni.getAppBaseInfo === 'function' ? uni.getAppBaseInfo() : {};

	if (windowInfo.windowWidth || windowInfo.windowHeight || windowInfo.statusBarHeight) {
		return {
			...deviceInfo,
			...appBaseInfo,
			...windowInfo,
			hostVersion: appBaseInfo.hostVersion || appBaseInfo.SDKVersion || appBaseInfo.version || '0.0.0',
		};
	}

	if (typeof uni.getSystemInfoSync === 'function') {
		return uni.getSystemInfoSync();
	}

	return {};
};

export const currentPage = (prefix = '/') => {
	let pages = getCurrentPages();
	return prefix + pages[pages.length - 1].route;
};

/**
 * 加密处理
 */
export function getASCII(data) {
	let openid = cache.get('openid')
	let openid_md = md5.hex_md5(openid)
	let res = `${openid_md}${ASCII.sort_ascii(data)}${openid_md}`
  return md5.hex_md5(res).toString().toUpperCase();
}
//ASCII加密
export function setASCII(data) {
  return ASCII.sort_ascii(data);
}
