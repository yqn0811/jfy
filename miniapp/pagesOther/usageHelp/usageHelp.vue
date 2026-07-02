<template>
  <view class="page">
    <view class="hero">
      <view class="hero-mark">?</view>
      <view class="hero-copy">
        <text class="hero-title">使用帮助</text>
        <text class="hero-desc">产品、分类、图片上传和分享的常见问题</text>
      </view>
    </view>

    <view class="quick-card">
      <text class="quick-title">推荐流程</text>
      <view class="steps">
        <view class="step" v-for="(step, index) in steps" :key="index">
          <text class="step-index">{{ index + 1 }}</text>
          <text class="step-text">{{ step }}</text>
        </view>
      </view>
    </view>

    <view class="section" v-for="section in sections" :key="section.title">
      <text class="section-title">{{ section.title }}</text>
      <view class="qa-card" v-for="item in section.items" :key="item.q">
        <view class="question-row">
          <text class="q-mark">Q</text>
          <text class="question">{{ item.q }}</text>
        </view>
        <text class="answer">{{ item.a }}</text>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      steps: [
        "先完善商户资料，让客户看到头像、名称和联系方式",
        "建立一级分类，再按系列或场景建立子分类",
        "新建产品，上传花色图、详情图，并绑定分类",
        "需要大量传图时，复制批量上传链接到浏览器处理",
      ],
      sections: [
        {
          title: "分类和产品",
          items: [
            {
              q: "一级分类和子分类怎么用？",
              a: "一级分类用于主页展示和管理大类，子分类用于细分系列。子分类下不再继续新建下级，避免层级过深导致客户找不到内容。",
            },
            {
              q: "产品和分类是什么关系？",
              a: "产品是具体款式或花型集合，分类只是整理产品的入口。一个产品可以被放到对应分类里，方便客户按系列浏览。",
            },
          ],
        },
        {
          title: "图片上传",
          items: [
            {
              q: "产品里应该上传哪些图片？",
              a: "花色图用于客户快速选款，详情图用于展示材质、场景和细节。封面默认优先取第一张可用图片，也可以后续单独设置。",
            },
            {
              q: "可以从资源库选择图片吗？",
              a: "可以。新建或编辑产品时选择“从资源库获取”，从 AI 生图资源库中勾选图片后，会带回到当前产品继续保存。",
            },
            {
              q: "上传失败通常怎么处理？",
              a: "先确认图片格式为常见图片格式，网络稳定后重试。一次上传数量较多时，建议分批上传或使用大批量上传入口。",
            },
          ],
        },
        {
          title: "大批量上传",
          items: [
            {
              q: "为什么小程序里不能一次上传很多张？",
              a: "微信小程序对文件选择和上传有数量限制。大量图片建议使用浏览器上传，速度和稳定性都会更好。",
            },
            {
              q: "大批量上传入口在哪里？",
              a: "进入产品编辑页，先保存产品，再选择“大批量上传”。系统会生成该产品专属上传链接。",
            },
            {
              q: "批量上传链接怎么用？",
              a: "复制链接到手机或电脑浏览器打开，也可以发给同事协助上传。上传的图片只会进入这个产品，不会跑到其它产品里。",
            },
            {
              q: "正式批量上传网址是什么？",
              a: "正式入口使用 pic.jfyuntu.com。复制链接后用浏览器打开，不要在微信聊天窗口里直接长时间上传大量图片。",
            },
          ],
        },
        {
          title: "分享和权限",
          items: [
            {
              q: "分享主页、分类、产品有什么区别？",
              a: "分享主页会展示商户主页；分享分类只进入该分类；分享产品只进入具体产品。客户只能看到被分享范围内允许展示的内容。",
            },
            {
              q: "客户打开分享后能删除我的内容吗？",
              a: "正常不会。客户访问分享页是访客视角，不应该进入你的工作台或删除你的产品。发现异常请截图并通过意见反馈提交。",
            },
            {
              q: "私密和仅分享可见有什么区别？",
              a: "私密内容只有自己可见；仅分享可见不会在主页公开展示，但拿到分享链接的人可以访问。",
            },
          ],
        },
      ],
    };
  },
};
</script>

<style scoped lang="scss">
.page {
  min-height: 100vh;
  background: #f6f6f6;
  padding: 40rpx 24rpx 56rpx;
  box-sizing: border-box;
}

.hero {
  display: flex;
  align-items: center;
  gap: 20rpx;
  padding: 28rpx 24rpx;
  background: #ffffff;
  border-radius: 20rpx;
}

.hero-mark {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  background: #ffd000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40rpx;
  font-weight: 700;
  color: #222222;
  flex-shrink: 0;
}

.hero-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.hero-title {
  font-size: 34rpx;
  font-weight: 700;
  color: #222222;
  line-height: 1.3;
}

.hero-desc {
  margin-top: 8rpx;
  font-size: 24rpx;
  color: #888888;
  line-height: 1.5;
}

.quick-card,
.qa-card {
  background: #ffffff;
  border-radius: 18rpx;
  box-sizing: border-box;
}

.quick-card {
  margin-top: 20rpx;
  padding: 24rpx;
}

.quick-title,
.section-title {
  display: block;
  font-size: 30rpx;
  font-weight: 700;
  color: #222222;
}

.steps {
  margin-top: 18rpx;
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.step {
  display: flex;
  align-items: flex-start;
  gap: 14rpx;
}

.step-index {
  width: 34rpx;
  height: 34rpx;
  border-radius: 50%;
  background: #222222;
  color: #ffd000;
  font-size: 22rpx;
  font-weight: 700;
  text-align: center;
  line-height: 34rpx;
  flex-shrink: 0;
  margin-top: 2rpx;
}

.step-text {
  flex: 1;
  min-width: 0;
  color: #555555;
  font-size: 26rpx;
  line-height: 1.55;
}

.section {
  margin-top: 32rpx;
}

.qa-card {
  margin-top: 16rpx;
  padding: 24rpx;
}

.question-row {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
}

.q-mark {
  width: 36rpx;
  height: 36rpx;
  border-radius: 10rpx;
  background: #fff5c2;
  color: #222222;
  font-size: 22rpx;
  font-weight: 700;
  text-align: center;
  line-height: 36rpx;
  flex-shrink: 0;
  margin-top: 2rpx;
}

.question {
  flex: 1;
  min-width: 0;
  font-size: 28rpx;
  font-weight: 700;
  color: #222222;
  line-height: 1.45;
}

.answer {
  display: block;
  margin-top: 14rpx;
  padding-left: 48rpx;
  font-size: 26rpx;
  color: #666666;
  line-height: 1.65;
}
</style>
