import config from '../config.js';

import {
	showToast,
} from '../helper/base.js';

/**
 * 上传类
 */
class Upload {
	constructor() {}

	/**
	 * 批量上传图片
	 * @param {Object} paths
	 */
	uploadMutiple(paths) {
		return new Promise((resolve) => {
			let img_url_ok = [];
			paths.forEach((path) => {
				this.upload(path).then((res) => {
					img_url_ok.push(res.data.url);
					if (img_url_ok.length == paths.length) {
						return resolve(img_url_ok);
					}
				});
			});
		});
	}

	/**
	 * 上传单图
	 * @param {Object} path
	 */
	upload(path, token) {
		console.log(path,'00000000')
		console.log(`Bearer ${token}`)
		return new Promise((resolve, reject) => {
			wx.uploadFile({
				url: `${config.domain}/api/file/upload`,
				filePath: path,
				name: 'file',
				header: {
					'content-type': 'multipart/form-data', // 默认值
					'authorization': `Bearer ${token}` // 携带token的请求头
				},
				success: (res) => {
					return resolve(JSON.parse(res.data));
				},
				fail: (res) => {
					console.log(res)
					showToast('上传图片失败,请重试');
					return reject(!1);
				},
			});
		});
	}
}

export default Upload;