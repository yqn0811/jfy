jQuery(document).ready(function() {
	$("input:checkbox, input:radio, input:file").uniform(); //单选复选美化
	//时分秒年月日范围
	$('.datetimepicker_box').datePicker({
		hasShortcut: true,
		isRange: true,
		shortcutOptions: [{
			name: '今天',
			day: '0,0',
			time: '00:00:00,23:59:59'
		},{
			name: '昨天',
			day: '-1,-1',
			time: '00:00:00,23:59:59'
		},{
			name: '最近1周',
			day: '-7,0',
			time:'00:00:00,23:59:59'
		}, {
			name: '最近1个月',
			day: '-30,0',
			time: '00:00:00,23:59:59'
		}, {
			name: '最近3个月',
			day: '-90, 0',
			time: '00:00:00,23:59:59'
		}, {
			name: '未来1周',
			day: '0, 7',
			time: '00:00:00,23:59:59'
		}, {
			name: '未来1个月',
			day: '0, 30',
			time: '00:00:00,23:59:59'
		}, {
			name: '未来3个月',
			day: '0, 90',
			time: '00:00:00,23:59:59'
		}, {
			name: '未来1年',
			day: '0, 365',
			time: '00:00:00,23:59:59'
		}],
		hide: function (type) {
			//console.info(this.$input.eq(0).val(), this.$input.eq(1).val());
			// console.info('类型：',type)
		}
	});

	//年月日范围
	$('.datetimepicker_box_date').datePicker({
		hasShortcut: true,
		isRange: true,
		format: 'YYYY-MM-DD',
		shortcutOptions: [{
			name: '今天',
			day: '0,0'
		},{
			name: '昨天',
			day: '-1,-1'
		},{
			name: '最近一周',
			day: '-7,0'
		}, {
			name: '最近一个月',
			day: '-30,0'
		}, {
			name: '最近三个月',
			day: '-90, 0'
		}],
		hide: function (type) {
			//console.info(this.$input.eq(0).val(), this.$input.eq(1).val());
			//console.info('类型：',type)
		}
	});

	//时分秒年月日单个
	$('.datetimepicker_datetime').datePicker({
		hasShortcut:true,
		shortcutOptions:[{
			name: '今天',
			day: '0'
		}, {
			name: '昨天',
			day: '-1'
		}, {
			name: '一周前',
			day: '-7'
		},{
			name: '明天',
			day: '1'
		},{
			name: '一周后',
			day: '7'
		},{
			name: '一个月后',
			day: '30'
		},{
			name: '一年后',
			day: '365'
		}],
		hide:function(){
			console.info(this)
		}
	});

	//年月日单个
	$('.datetimepicker_date').datePicker({
		hasShortcut:true,
		format:'YYYY-MM-DD',
		shortcutOptions:[{
			name: '今天',
			day: '0'
		}, {
			name: '昨天',
			day: '-1'
		}, {
			name: '一周前',
			day: '-7'
		},{
			name: '明天',
			day: '1'
		},{
			name: '一周后',
			day: '7'
		},{
			name: '一个月后',
			day: '30'
		},{
			name: '一年后',
			day: '365'
		}],
		hide:function(){
			console.info(this)
		}
	});

	//时分秒单个
	$('.datetimepicker_daytime').datePicker({
		hasShortcut:true,
		format:'HH:mm:ss',
		hide:function(){
			console.info(this)
		}
	});

	// tabs_v3 hgj 20220520
	$('.tabs_v3_hover>ul>li').hover(function () {
		$(this).parent('ul').find("li").removeClass('tabs_v3_active');
		$(this).addClass("tabs_v3_active")
		let tab_index = $(this).index()
		$('.tabs_v3_content_box').hide()
		$('.tabs_v3_content_box').eq(tab_index).show()
	})
	$('.tabs_v3_click>ul>li').click(function () {
		$(this).parent('ul').find("li").removeClass('tabs_v3_active');
		$(this).addClass("tabs_v3_active")
		let tab_index = $(this).index()
		$('.tabs_v3_content_box').hide()
		$('.tabs_v3_content_box').eq(tab_index).show()
	})
});
function copyid(id){
	var clipboard = new Clipboard('.js-clip'+id);
	clipboard.on('success', function(e) {
		layer.msg('复制成功', {icon: 1});
		e.clearSelection();
	});
	clipboard.on('error', function(e) {
		alert("复制失败");
	});
}
//layer
function showMsg(_msgs,_icon,_time,_url){
	layer.open({
		title: '提示',
		content: _msgs,
		time: _time,
		icon: _icon,
		closeBtn: 0,
		btn: [],
		end: function(){
			if(_url){
				window.location = _url
			}
		},
	})
}
// 本地选择单张图片
function chooseImg(obj,i) {
	// 检测图片大小
	var input_size = document.getElementById("imgSize"+i);
	if (input_size.files) {
		var files = input_size.files;
		for (var j in files) {
			console.log(files[j]);
			if (parseInt(files[j].size / 1024) > 512) {
				alert("上传的图片大小超过512kb,请重新上传")
				return false
			}
		}
	}
	// 上传图片
	var file = obj.files[0];
	var reader = new FileReader();
	reader.readAsDataURL(file);
	reader.onload = function (e) { //成功读取文件
		var img = document.getElementById("selectImg"+i);
		img.src = e.target.result;
		var ipt = document.getElementById("localchooseimg" + i);
		ipt.value = e.target.result;
	};
}
var path ="1";
$("body").delegate(".commonchangepic", "click",function(){
	var type =  $(this).data('type');
	var classname = $(this).find("input").attr("name");
	if(!classname){
		var addCard_type = $(this).data('class');
		var addCard_index = $(this).data('index');
	}
	layer.open({
		skin: 'libaray-layui',
		type: 2,
		title: false,
		area: ['1120px'],
		fixed: false, //不固定
		closeBtn:0,
		maxmin: false,
		content: [common_img_url+'?type='+type,'no'],
		success:function(layero,index){
		},
		end:function(){
			if(classname){
				if(type==2){
					var handle_status = $("#handle_status").val();
					if(handle_status!=""){
						$("input[name="+classname+"]").val(handle_status)
						var imgarr = handle_status.split(',');
						var str = "";
						var imginput = "";
						var idx = $("input[name='imgsrcs[]']:last").attr('class')
						console.log("idx")
						console.log(idx)
						if(idx == undefined){
							idx = '';
						}else{
							idx = idx.substring(11);
							console.log("idx2")
							console.log(idx)
						}
						let j = 0;
						for(var i=0; i<imgarr.length; i++){
							if(idx != ''){
								j = idx*1 + 1;
							}
							str+="<div class='thumbnail img_multi"+j+"'>"+
							"<img src='"+imgarr[i]+"'>"+
							"<a class='del_img_v3' href='javascript:;' onclick='del_imgs_v3("+j+")'>删除</a>"+
							"</div>";
							imginput += '<input type="hidden" name="imgsrcs[]" value="'+imgarr[i]+'" class="del_img_num'+j+'" />';
							if(idx != ''){
								++idx;
							}else{
								++j;
							}
						}
						if(str){
							$(".commonuploadslide").append(str);
						}
						if(imginput){
							$(".commonuploadslide").append(imginput);
						}
						$("#handle_status").val("");
					}
				}
				if(type==1){
					var nowhtml = $("#nowhtml").attr("class");
					var handle_status = $("#handle_status").val();
					if(handle_status){
						var len = classname.split("commonuploadpic");
						if(nowhtml == 'navSystem21'){ //商品海报设置背景图实时
							if(classname == 'commonuploadpic1'){
								$(".poster_bg").attr('src', handle_status)
							}
							if(classname == 'commonuploadpic2'){
								$(".poster_bg1").attr('src', handle_status)
							}
						}
						if(nowhtml == 'navModel26-1'){ //开屏优惠券图片
							if(classname == 'commonuploadpic3'){
								$("#now_topImg").attr('src', handle_status)
							}
						}
						if(nowhtml == 'navModel29-1'){ //生日祝福弹框图片
							if(classname == 'commonuploadpic2'){
								$("#now_topImg").attr('src', handle_status)
							}
						}
						if(nowhtml == 'navModel36-1'){ //新人有礼弹框图片
							if(classname == 'commonuploadpic3'){
								$("#now_topImg").attr('src', handle_status)
							}
						}
						if(nowhtml == 'navModel49-2'){ //打卡活动 海报背景
							if(classname == 'commonuploadpic1'){
								$("#posterBox_bg").attr('src', handle_status)
							}
						}

						if(nowhtml == 'navModel57-1'){ //名片分类 图片选择 class带[]
							$("input[name='"+classname+"']").val(handle_status)
							let _classname = classname.replace(/\[|]/g,'')
							$("."+_classname+" img").attr("src",handle_status)
						}else{  //普通图片选择
							if(len[1] < 10){
								$("input[name="+classname+"]").val(handle_status)
								$("."+classname+" img").attr("src",handle_status)
							}else{
								var len_i = classname.split("commonuploadpic1");
								var imgxs = "#imgxs"+len_i[1];
								$("#imgurl"+len_i[1]).val(handle_status);
								$(imgxs+" img").html("");
								var imgurl = "<img src="+handle_status+"><div class='xuzndt1' name='imgurl"+len_i[1]+"' onclick='delimage(imgurl"+len_i[1]+")'><img src=/image/guige_delete.png></div>";
								$(imgxs).html(imgurl);
							}
						}
						$("#handle_status").val("");
					}
				}
			}else{
				let contens = $('#content-box').val();
				let contents_arr = JSON.parse(contens);
				var imgarr = [];
				if(type == 2){
					var handle_status = $("#handle_status").val();
					if(handle_status){
						imgarr = handle_status.split(',');
						if(addCard_type == 's_image'){  //小图
							contents_arr[addCard_index]['content'].push(...imgarr);
							$('#content-box').val(JSON.stringify(contents_arr))
						}else{  //单张大图
							imgarr.forEach((item,index) => {
								if(index == 0){
									contents_arr[addCard_index]['content'] = item
								}else{
									let obj = {
										content:item,
										type:'image',
										type_text: '大图'
									}
									contents_arr.splice(addCard_index + index,0,obj)
								}
							})
							$('#content-box').val(JSON.stringify(contents_arr))
						}
						$("#handle_status").val("");
						dealMoudleShow()
					}
				}
			}
		}
	});
});


function saveUditor(_ue) {
	//编辑器模式切换
	if(_ue.queryCommandState('source')!=0){ //判断编辑模式状态:0表示【源代码】HTML视图；1是【设计】视图,即可见即所得；-1表示不可用
		_ue.execCommand('source'); //切换到【设计】视图
	}
}

// 推广 start
$(document).on('click',function(event){
	var target = $(event.target);
	if(!target.is('.noclose') && !target.parent().is('.noclose')){
		$('.promotionBox').fadeOut('medium');
		$('.page-container').css({'overflow':'hidden'});
	}
})
let to_promotion_title = '';
function getPromotion(_url,_unid,_id,_type='',pagain=false){
	if($('.promotionBox_'+_id).css('display') != 'none' && !pagain){
		$('.promotionBox_'+_id).fadeOut('medium');
		$('.page-container').css({'overflow':'hidden'});
	}else{
		var now_load =  layer.open({
			title: '提示',
			content: '加载中...',
			icon: 0,
			closeBtn: 0,
			btn: []
		})
		$.ajax({
			url: _url,
			type: "post",
			dataType: 'json',
			data:{
				uniacid: _unid,
				pid: _id,
				get_true: pagain
			},
			success:function(res){
				layer.close(now_load);
				if(res.code == 1){
					to_promotion_title = res.data.pro_name;
					let topOffset = $('.goodsPromotion_'+_id).offset().top;
					$('.page-container').css({'overflow':'visible'});
					if(topOffset > 500){
						$('.promotionBox .triangle_show').css({'transform':'rotate(-45deg)','top':'unset','bottom':'-5px'});
						$('.promotionBox').css({'bottom':_type == 'moban' ? '28px' : '45px','top':'unset'});
					}else{
						$('.promotionBox .triangle_show').css({'transform':'rotate(135deg)','top':'-5px','bottom':'unset'});
						$('.promotionBox').css({'bottom':'unset','top':_type == 'moban' ? '28px' : '45px'});
					}
					if(!pagain){
						$('.promotionBox').hide();
						$('.promotionBox_'+_id).fadeIn('medium');
					}
					let _datas = res.data.result;
					let _len = Object.keys(_datas).length;
					let _first = Object.keys(_datas)[0];
					let _html1 = '', _html2 = '';
					for(let key in _datas){
						_html1 += `<div class="topItem topItem${_len} topItem_${key}" onclick="changeShowPro('${key}')">${key == 'wx_info' ? '微信' : (key == 'h5_info' ? 'H5' : (key == 'account_info' ? '公众号' : ''))}<span class="act_line"></span></div>`;
						_html2 += `<div class="noclose codeShow codeShow_${key}">
												<div class="flex_bbox noclose">
													<div>二维码：<span class="text-primary cursor-pointer" onclick="downCodePro('${key}')">下载二维码</span></div>
													<div class="text-primary cursor-pointer noclose" onclick="AgainPromotion('${_url}','${_unid}','${_id}','${_type}',true)">重新获取</div>
												</div>
												<div class="noclose"><img src="${_datas[key].ewm_link}"></div>
												<div>页面链接：<span class="noclose text-primary cursor-pointer" onclick="copyLinkPro('${key}-${_id}')" id="${key}-${_id}_cp">复制</span></div>
												<input type="hidden" id="${key}-${_id}" value="${_datas[key].path}" />
												<div class="url_show">${_datas[key].path}</div>
											</div>`
					}
					$('.promotionBox_'+_id).find('.flex_box').html(_html1);
					$('.promotionBox_'+_id).find('.codeBox').html(_html2);
					changeShowPro(_first);
				}else{
					layer.msg(res.msg,{time: 1000})
				}
			}
		})
	}
}
function changeShowPro(_type){
	event.stopPropagation();
	$('.topItem').removeClass('topItem_act');
	$('.topItem_'+_type).addClass('topItem_act');
	$('.codeShow').hide();
	$('.codeShow_'+_type).show();
}
function AgainPromotion(_url,_unid,_id,_type,pagain){
	getPromotion(_url,_unid,_id,_type,pagain);
}
function copyLinkPro(id){
	var clipboard = new Clipboard('#'+id+'_cp', {
		text: function (trigger) {
			layer.msg('复制成功', {time: 600})
			return $("#"+id).val()
		}
	})
}
// 推广 end

