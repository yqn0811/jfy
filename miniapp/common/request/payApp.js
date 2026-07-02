import {
	showToast,
} from '../helper/base.js';
/**
 * 支付类
 * @pay_for 支付对象
 */
class PayApp {
	// constructor(pay_for = null) {
	// 	this.pay_api = pay_for && API[pay_for].value;
	// 	this.after_pay_api = pay_for && API[pay_for].after;
	// }
	/**
	 * 吊起微信支付
	 */
	reuqestMiniPay(orderResult) {
		let order_id = orderResult.order_id;
		return new Promise((resolve, reject) => {
			wx.requestPayment({
				...orderResult.pay_info,
				success: (res) => {
					// showToast('支付成功');
					return resolve({
						status: 1,
						order_id,
					});
				},
				fail: (err) => {
					console.log('支付fail:' + JSON.stringify(err));
					if ('requestPayment:fail cancel' === err.errMsg) {
						showToast('您已取消支付');
						return resolve({
							status: '-1',
							order_id,
							orderResult: orderResult,
						});
					} else {
						return reject(false);
					}
				},
			});
		});
	}

}

export default PayApp