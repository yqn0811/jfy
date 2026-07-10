import config from '../config.js';

import {
	showToast,
} from '../helper/base.js';
import { buildAuthHeader } from '../helper/auth.js';

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
		return Promise.all(paths.map((path) => this.upload(path))).then((results) => {
			return results.map((res) => res.data && (res.data.url || res.data.full_url)).filter(Boolean);
		});
	}

	/**
	 * 上传单图
	 * @param {Object} path
	 */
	upload(path, token = '') {
		return new Promise((resolve, reject) => {
			const header = {
				'content-type': 'multipart/form-data',
				...buildAuthHeader(token),
			};
			wx.uploadFile({
				url: `${config.domain}/api/file/upload`,
				filePath: path,
				name: 'file',
				header,
				success: (res) => {
					try {
						return resolve(JSON.parse(res.data));
					} catch (e) {
						showToast('上传响应异常,请重试');
						return reject(e);
					}
				},
				fail: (res) => {
					showToast('上传图片失败,请重试');
					return reject(res);
				},
			});
		});
	}
}

export default Upload;
