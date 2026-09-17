//单选
var appletid = "";
var datatype = "";
var gettype = "";
var datahtml = ""; //页面需要处理内容包含html代码片段
var reserveValues = ""; //存储选中的值
$(document).on("click", '[data-fade="tendaddUrl"]', function () {
    appletid = $(this).attr("data-uniacid");
    datatype = $(this).attr("data-type") || "single";
    gettype = $(this).attr("data-additem") || "";
    datahtml = $(this).attr("data-html") || ""; //页面需要处理内容包含html代码片段后续html插入的id值
    let suid = $(this).attr("data-suid") || "";
    console.log(datahtml);
    reserveValues = $(this).attr("data-hidden-values") || ""; //存储选中的值 避免重复插入

    var jump_url = "/index/WxuserController/personSelector.html?appletid=" + appletid;

    if (datatype) {
        jump_url = jump_url + "&datatype=" + datatype;
    }

    if (gettype) {
        jump_url = jump_url + "&gettype=" + gettype;
    }

    layer.open({
        skin: "",
        type: 2,
        title: false,
        area: ["740px", "664px"],
        fixed: false, //不固定
        closeBtn: 0,
        content: [jump_url, "no"],
        success: function (layero, index) {},
        end: function () {
            var res = $("#handle_status").val();
            let parse_res = JSON.parse(res);
            if (datatype == "single") {
                parent.$(".pop_set").val(parse_res.nickname);

                parent.$("#groupId").val(parse_res.id);
                parent.$("#selcet-dx").attr("data-id", parse_res.id);
                parent.$("#selcet-dx").val(parse_res.nickname);
                if (datahtml == "dealer-html") {

                    console.log(suid)
                    $.ajax({
                        url: "/index/fx/changebindfxs.html?appletid="+appletid,
                        type: "post",
                        dataType: "json",
                        data: {
                            uniacid: appletid,
                            suid: suid,
                            parent_id: parse_res.id,
                        },
                        success: function (res) {
                            var res = JSON.parse(res);
                            if (res.error == 1) {
                                alert(res.msg);
                                return false;
                            }
                            location.reload();
                        },
                    });
                }else if (datahtml == "fxup") {
                    $.ajax({
                        url: "/index/wxuser/changeuserparentfxsuser.html?appletid="+appletid,
                        type: "post",
                        dataType: "json",
                        data: {
                            uniacid: appletid,
                            suid: suid,
                            new_fxs_id: parse_res.id,
                        },
                        success: function (res) {
                            if(res.code == 1){
                                $(".con2_show4").html(parse_res.nickname)
                            }else{
                                alert(res.msg);
                                return false
                            }
                        },
                    });
                }
            } else {
                if (datahtml) {
                    let selectValues = [];
                    //获取当前选中的值避免重复插入
                    let reserveValuesArr = $(`#${reserveValues}`).val();
                    if (reserveValuesArr) {
                        selectValues = reserveValuesArr.split(",");
                    }
                    let html = ``;
                    if (datahtml == "davchMule") {
                        //回显页面

                        parse_res.forEach((res) => {
                            if (!selectValues.includes(res.id + "")) {
                                selectValues.push(res.id + "");
                                html += `<li class="select2-selection__choice" id="${res.id}"  name="${res.nickname}"><span class="select2-selection__choice__remove">${res.nickname}</span><i onclick="delItem(${res.id})">×</i></li>`;
                            }
                        });
                    } else if (datahtml == "yhmess") {
                        //新增核销员
                        parse_res.forEach((res) => {
                            if (!selectValues.includes(res.id + "")) {
                                selectValues.push(res.id + "");
                                html += `<tr class="muletr" id="${res.id}" data-id="${res.id}" style="vertical-align: middle;padding:0.75rem;text-align: center;"><td style="padding:0.75rem;vertical-align: middle;text-align: center;" class="muletd"><input class="muleclassid" name="user_id[]" id="" value="${res.id}" type="hidden"><div class="selection-message-left" id="${res.id}"><div class="selection-person"><img src="${res.avatar}" alt=""></div><div class="selection-message"><div class="selection-message-top"><span class="selection-message-top-title">昵称</span><a id="selection-user-mess" class="selection-user selection-message-user" href="javascript:void(0)">${res.nickname}</a></div><div class="selection-message-bottom"><span class="selection-message-top-txt">手机</span><span class="selection-message-user">${res.phone}</span></div></div></div><input data-id="${res.id}" type="hidden" value=""><div class="selection-message-right" id="radio-right" style="display: none;"><span class="fr pointer popclose"><i class="iconfont icon-x-guanbi" onclick="delteem(${res.id})"></i></span></div></td><td>
                    <input type="text" data-id="${res.id}" id="beizhu" name="names[]" value></td><td><div class="selection-message-right" id="radio-right"><a style="color:#000;" class="iconfont  dellistitem" onclick="delList(${res.id},'${reserveValues}')">删除</a></div></td></tr>`;
                            }
                        });
                    } else if (datahtml == "yhmess-manager") {
                        parse_res.forEach((res) => {
                            if (!selectValues.includes(res.id + "")) {
                                selectValues.push(res.id + "");
                                html += `<tr class="muletr" id="${res.id}" style="vertical-align: middle;padding:0.75rem;vertical-align: middle;text-align: center;">
										<td style="vertical-align: middle;padding:0.75rem;vertical-align: middle;text-align: center;" class="muletd">
												<input class="muleclassid" name="user_id[]" id="" value="${res.id}" type="hidden">
												<li class="li_act li_act_right hxmmtableLi" id="${res.id}">
													<div class="selection-message-left" id="${res.id}">
														<div class="selection-message">
															<div class="selection-message-top">
																<!-- <span class="selection-message-top-title">昵称</span> -->
																<a id="selection-user-mess" class="selection-user selection-message-user" href="javascript:void(0)">${res.nickname}</a>
															</div>
														</div>
													</div>
												</li>
										</td>
										<td>
											<input id="beizhu" type="text" name="names[]" value="" onclick="derVal()">
										</td>
										<td>
											<div class="selection-message-right" id="radio-right">
												<div class="btn btn-warning mr-10 float-left" onclick="showPower(${res.id})">分配权限</div>
												<a style="color:#000;" class="iconfont dellistitem" onclick="delList(${res.id},'${reserveValues}')">删除</a>
											</div>
										</td>
									</tr>`;
                            }
                        });
                    }
                    parent.$(`#${datahtml}`).append(html);
                    if (reserveValues) {
                        $(`#${reserveValues}`).val(selectValues.join(","));
                    }
                }
            }
        },
    });
});
function delItem(id) {
    if (reserveValues) {
        let reserveValuesArr = $(`#${reserveValues}`).val();

        if (reserveValuesArr) {
            let selectValues = reserveValuesArr.split(",");
            let find_index = selectValues.findIndex((value) => Number(value) == id);
            selectValues.splice(find_index, 1);

            $(`#${reserveValues}`).val(selectValues.join(","));
        }
    }
    $("#" + id).remove();
}

// 删除操作
function delList(id, reserveValues) {
    if (reserveValues) {
        let reserveValuesArr = $(`#${reserveValues}`).val();
        if (reserveValuesArr) {
            let selectValues = reserveValuesArr.split(",");
            let find_index = selectValues.findIndex((value) => Number(value) == id);
            selectValues.splice(find_index, 1);

            $(`#${reserveValues}`).val(selectValues.join(","));
        }
    }
    $("#" + id).remove();
    // if($(`#${datahtml} tr`).length == ''){
    //   $(".control-group-moreinput table").remove();
    // }
}
