import config from '../config.js';

import {
	showToast,
} from '../helper/base.js';
import { buildAuthHeader } from '../helper/auth.js';

const DEFAULT_UPLOAD_ENDPOINT = '/api/file/upload';

const buildUploadUrl = (endpoint = DEFAULT_UPLOAD_ENDPOINT) => {
	if (/^https?:\/\//.test(endpoint)) return endpoint;
	if (endpoint.charAt(0) === '/') return `${config.domain}${endpoint}`;
	return `${config.host}/${endpoint}`;
};

const parseUploadResponse = (response) => {
	const rawData = response && response.data !== undefined ? response.data : response;
	if (typeof rawData === 'string') {
		return JSON.parse(rawData || '{}');
	}
	return rawData || {};
};

const getUploadResultUrl = (response) => {
	const payload = Array.isArray(response && response.data)
		? response.data[0] || {}
		: (response && response.data) || response || {};
	return (
		payload.full_url ||
		payload.url ||
		payload.imgurl ||
		payload.src ||
		payload.fileUrl ||
		payload.picture_url ||
		''
	);
};

/**
 * 上传类
 */
class Upload {
	constructor() {}

	/**
	 * 批量上传图片
	 * @param {Object} paths
	 */
	uploadMutiple(paths, options = {}) {
		return Promise.all(paths.map((path) => this.upload(path, options))).then((results) => {
			return results.map((res) => getUploadResultUrl(res)).filter(Boolean);
		});
	}

	/**
	 * 上传单图
	 * @param {Object} path
	 */
	upload(path, tokenOrOptions = '') {
		const options = typeof tokenOrOptions === 'object'
			? tokenOrOptions
			: { token: tokenOrOptions };
		const {
			token = '',
			endpoint = DEFAULT_UPLOAD_ENDPOINT,
			formData = {},
			name = 'file',
			header: customHeader = {},
			showErrorToast = true,
		} = options;

		return new Promise((resolve, reject) => {
			const header = {
				'content-type': 'multipart/form-data',
				...customHeader,
				...buildAuthHeader(token),
			};
			wx.uploadFile({
				url: buildUploadUrl(endpoint),
				filePath: path,
				name,
				header,
				formData,
				success: (res) => {
					try {
						return resolve(parseUploadResponse(res));
					} catch (e) {
						if (showErrorToast) showToast('上传响应异常,请重试');
						return reject(e);
					}
				},
				fail: (res) => {
					if (showErrorToast) showToast('上传图片失败,请重试');
					return reject(res);
				},
			});
		});
	}
}

export default Upload;
