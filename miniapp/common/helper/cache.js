import config from '../config';

import {
	getCurrentTimeStamp
} from './time.js';


/**
 * @author Azal
 * @date 2020/4/29 09:56
 * @description 缓存
 */
class Cache {
	constructor() {}

	_buildCacheKey(key) {
		return `${key}${config.environment}${config.domain.substr(-6)}`;
	}

	/**
	 * 读取缓存
	 */
	get(key) {
		let data = uni.getStorageSync(this._buildCacheKey(key));
		if (data && data instanceof Object) {
			if (data.hasOwnProperty('expired_time')) {
				if (getCurrentTimeStamp() > data.expired_time) {
					this.remove(key);
					return null;
				}
				return data.data;
			}
			return data;
		}
		const legacyData = uni.getStorageSync(key);
		return legacyData || null;
	}

	/**
	 * 设置缓存
	 * @param {String} key
	 * @param {Object} data
	 * @param {Int} _ttl 时效 秒
	 */
	set(key, data, _ttl = 0) {
		if (_ttl) {
			data = {
				data,
				expired_time: _ttl + getCurrentTimeStamp()
			};
		}
		uni.setStorageSync(this._buildCacheKey(key), data);
	}

	/**
	 * 异步设置缓存
	 * @param {Object} key
	 * @param {Object} data
	 * @param {Int} _ttl 时效 秒
	 */
	setSync(key, data, _ttl = 0) {
		if (_ttl) {
			data = {
				data,
				expired_time: _ttl + getCurrentTimeStamp()
			};
		}
		key = this._buildCacheKey(key);
		return new Promise((resolve, reject) => {
			uni.setStorage({
				key,
				data,
				success: () => {
					return resolve(!0);
				},
				fail: () => {
					return reject(!1);
				}
			});
		});
	}
	
	batchRemove(keys) {
		keys.forEach((key) => {
			this.remove(key)
		})
	}

	/**
	 * 清除缓存
	 * @param {Object} key
	 */
	remove(key) {
		uni.removeStorageSync(this._buildCacheKey(key));
		uni.removeStorageSync(key);
	}
}

export default Cache;
