var _appletid = $("input[name='appletid']").val()

$(function () {
  // 编辑时是否有值
  let con_content = $('#content-box').val()
  if(con_content){
    dealMoudleShow()
  }


  // 添加模块
  $('.dragon-box-btn .btn').click(function () {
    $('.empty-show').css('display', 'none')
    let type = $(this).data('type')
    addModuleDown(type)
  })
})
function initContent() {
  $('.empty-show').css('display', 'none')
  addModuleDown('textarea')
}
/**
* 渲染页面模块
*/
function dealMoudleShow() {
  let contens = $('#content-box').val();
  let arr = JSON.parse(contens);
  let html = ``;
  if (arr.length > 0) {
    arr.forEach((item, index) => {
      html += `<div class="mt-15">
                  <div class="row">
                      <div class="col-sm-8"><div class="title">`+ item.type_text + `</div></div>
                      <div class="col-sm-4 text-right" style="line-height: 32px">
                          <div class="btn btn-default top-btn`+ (index == 0 ? ' btn-disabled' : '') + `" onclick="moveUp(${index})">上移</div>
                          <div class="btn btn-default top-btn`+ (index == arr.length - 1 ? ' btn-disabled' : '') + `" onclick="moveDown(${index})">下移</div>
                          <div class="btn btn-default top-btn`+ (index == 0 ? ' btn-disabled' : '') + `" onclick="setTop(${index})">置顶</div>
                          <div class="btn btn-default top-btn" data-toggle="dropdown" onclick="openMake(${index})">添加</div>
                          <ul id="makeBox_`+index+`" class="dropdown-menu module-dropdown-menu" role="menu">
                              <li><a href="#" onclick="addModuleDown('image',${index})">向下添加大图</a></li>
                              <li><a href="#" onclick="addModuleDown('s_image',${index})">向下添加小图</a></li>
                              <li><a href="#" onclick="addModuleDown('video',${index})">向下添加视频</a></li>
                              <li><a href="#" onclick="addModuleDown('textarea',${index})"">向下添加文字</a></li>
                          </ul>
                          <div class="btn btn-default top-btn" onclick="delMoudle(${index})">删除</div>
                      </div>
                  </div>`;
      if (item.type == 'image') {
        if (item.content.length) {
          html += `<div class="upload-image-show">
                              <img src="${item.content}" class="content-img"/>
                              <img class="del-icon" src="/image/delete_gray.png" onclick="delImg('image',${index})"/>
                           </div>`
        } else {
          html += `<div class="upload-image commonchangepic" data-type="2" data-class="image" data-index="${index}">
                      <div class="img-box">
                          <div class="iconfont icon-x-jia"></div>
                          <div>添加图片</div>
                      </div>
                  </div>`
        }
        html += `</div>`
      } else if (item.type == 's_image') {
        if (item.content.length) {
          item.content.forEach((img, img_index) => {
            html += `<div class="s-img-block">
                                  <img src="${img}"/>
                                  <img class="del-icon" src="/image/delete_gray.png" onclick="delImg('s_image',${index},${img_index})"/>
                               </div>`
          })
        }
        html += `<div class="upload-image s-upload-image commonchangepic" data-type="2" data-class="s_image" data-index="${index}">
                      <div class="img-box s-img-box">
                          <div class="iconfont icon-x-jia"></div>
                          <div>添加图片</div>
                      </div>
                   </div>
                 </div>`
      } else if (item.type == 'video') {
        if (item.content) {
          html += `<div class="upload-image-show v-upload-image-show">
                              <video src="${item.content}" class="content-img" controls/>
                              <img class="del-icon" src="/image/delete_gray.png" onclick="delImg('image',${index})"/>
                           </div>`
        } else {
          html += `<div class="upload-image v-upload-image" data-class="video" data-from="1" data-type="2" data-toggle="selectMediaSource" data-uniacid="`+_appletid+`" data-index="${index}">
                      <div class="img-box v-img-box vide_img`+index+`">
                          <div class="iconfont icon-shipin11"></div>
                          <div>添加视频</div>
                      </div>`
        }
        html += `</div>`
      } else if (item.type == 'textarea') {
        html += `<textarea autoHeight placeholder="请输入文字内容..." class="border-textarea" id="textarea` + index + `" data-index="` + index + `" onchange="changeTextArea(${index})">` + item.content + `</textarea></div>`
      }
      $('.module-box').html(html);
      $('.module-box').css('display', 'block')
    })
  } else {
    $('.empty-show').css('display', 'block');
    $('.module-box').css('display', 'none')
  }

}

/**
* 上移
* @param index  下标
*/
function moveUp(index) {
  let contens = $('#content-box').val();
  let arr = JSON.parse(contens);
  if (index == 0) {
    return false
  }
  let item = arr[index]
  arr.splice(index - 1, 0, item)
  arr.splice(index + 1, 1)
  $('#content-box').val(JSON.stringify(arr));
  //渲染页面显示
  dealMoudleShow()

}
/**
* 下移
* @param index  下标
*/
function moveDown(index) {
  let contens = $('#content-box').val();
  let arr = JSON.parse(contens);
  if (index == arr.length - 1) {
    return false
  }
  let item = arr[index]
  arr.splice(index + 2, 0, item)
  arr.splice(index, 1)
  $('#content-box').val(JSON.stringify(arr));
  //渲染页面显示
  dealMoudleShow()

}
/**
* 置顶
* @param index  下标
*/
function setTop(index) {
  let contens = $('#content-box').val();
  let arr = JSON.parse(contens);
  if (index == 0) {
    return false
  }
  let item = arr[index]
  arr.splice(index, 1)
  arr.unshift(item)
  $('#content-box').val(JSON.stringify(arr));
  //渲染页面显示
  dealMoudleShow()

}
/**
* 删除模块
* @param index  下标
*/
function delMoudle(index) {
  let contens = $('#content-box').val();
  let arr = JSON.parse(contens);
  arr.splice(index, 1)
  $('#content-box').val(JSON.stringify(arr));
  //渲染页面显示
  dealMoudleShow()

}

/**
* 向下添加模块
* @param type 模块类型
*/
function addModuleDown(type, index = -1) {
  let value = $('#content-box').val();
  let arr = value ? JSON.parse(value) : [];
  let obj = {}
  let text = '';
  if (type == 'image') {
    text = '大图'
  } else if (type == 's_image') {
    text = '小图'
  } else if (type == 'video') {
    text = '视频'
  } else if (type == 'textarea') {
    text = '文字'
  }
  obj = {
    type: type,
    content: type == 's_image' ? [] : '',
    type_text: text
  }
  if (index < 0) {
    arr.push(obj)
  } else {
    arr.splice(index + 1, 0, obj)
  }
  let arr_obj = JSON.stringify(arr)
  $('#content-box').val(arr_obj);
  //渲染页面显示
  dealMoudleShow()
}

/**
* 删除内容某一图片
* type:删除类型
* index:内容数组下标
* s_index:当前删除小图图片下标
*/
function delImg(type, index, s_index) {
  let arrs_show = $('#content-box').val();
  let arr = JSON.parse(arrs_show);
  if (type == 'image') {
    arr[index]['content'] = '';
  } else if (type == 's_image') {
    arr[index]['content'].splice(s_index, 1)
  }
  let arr_obj = JSON.stringify(arr)
  $('#content-box').val(arr_obj);
  //渲染页面显示
  dealMoudleShow()
}

function changeTextArea(index) {
  let arrs_show = $('#content-box').val();
  let arr = arrs_show ? JSON.parse(arrs_show) : [];
  let value = $(`#textarea${index}`).val();
  arr[index]['content'] = value;
  let arr_obj = JSON.stringify(arr)
  $('#content-box').val(arr_obj);
}

// 展开小盒子操作
function openMake(index){
  console.log(index)
  $("#makeBox_"+index).slideToggle();
}
