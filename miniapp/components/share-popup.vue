<template>
  <view class="popup-mask" :class="{ 'popup-show': show }" @click="handleClickMask">
    <view class="popup-content" :class="{ 'popup-content-show': show }" @click.stop>
      <!-- <view class="popup-title">
        <text>{{ title }}</text>
        <view class="download-icon" v-if="showDownloadIcon">
          <text class="iconfont">&#xe623;</text>
          <text>{{ downloadText }}</text>
        </view>
      </view> -->
      <view class="popup-options">
		  <button class="popup-option" plain style="margin: 0;padding: 0;line-height: 40rpx;border: 0;"
			open-type="share" >
			 <view class="option-icon share-icon">
			   <image src="../static/image/share.png" mode="aspectFit"></image>
			 </view>
			 <text class="option-text">分享好友</text>
		  </button>
       <!-- <view class="popup-option" @click="shareToFriend">
          <view class="option-icon share-icon">
            <image src="../static/image/share.png" mode="aspectFit"></image>
          </view>
          <text class="option-text">分享好友</text>
        </view> -->
        <view class="popup-option" @click="generatePoster">
          <view class="option-icon poster-icon">
            <image src="../static/image/shareimg.png" mode="aspectFit"></image>
          </view>
          <text class="option-text">生成海报</text>
        </view>
      </view>
      <view class="popup-cancel" @click="handleCancel">
        <text>取消</text>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  name: 'SharePopup',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: '保存图片并分享'
    },
    showDownloadIcon: {
      type: Boolean,
      default: false
    },
    downloadText: {
      type: String,
      default: '保存图片'
    },
    maskClosable: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    handleClickMask() {
      if (this.maskClosable) {
        this.$emit('update:show', false);
        this.$emit('cancel');
      }
    },
    handleCancel() {
      this.$emit('update:show', false);
      this.$emit('cancel');
    },
    shareToFriend() {
      this.$emit('share-friend');
    },
    generatePoster() {
      this.$emit('generate-poster');
    },
  }
}
</script>

<style lang="scss" scoped>
.popup-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 999;
  visibility: hidden;
  opacity: 0;
  transition: opacity 0.3s ease;
  
  &.popup-show {
    visibility: visible;
    opacity: 1;
  }
  
  .popup-content {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #fff;
    border-radius: 20rpx 20rpx 0 0;
    transform: translateY(100%);
    transition: transform 0.3s ease;
    
    &.popup-content-show {
      transform: translateY(0);
    }
    
    .popup-title {
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      height: 100rpx;
      border-bottom: 2rpx solid #f5f5f5;
      
      text {
        font-size: 32rpx;
        color: #333;
      }
      
      .download-icon {
        position: absolute;
        right: 30rpx;
        display: flex;
        align-items: center;
        
        text {
          font-size: 28rpx;
          color: #666;
          margin-left: 10rpx;
        }
      }
    }
    
    .popup-options {
      display: flex;
      justify-content: space-around;
      padding: 40rpx 0;
	  background-color:#F9F9F9 ;
      
      .popup-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        
        .option-icon {
          width: 110rpx;
          height: 110rpx;
          border-radius: 20rpx;
          display: flex;
          justify-content: center;
          align-items: center;
          margin-bottom: 20rpx;
          
         
        }
        
        .share-icon {
			width: 110rpx;
			height: 110rpx;
        }
        
        .poster-icon {
			width: 110rpx;
			height: 110rpx;
        }
        
        .option-text {
         font-size: 24rpx;
         color: #666666;
        }
      }
    }
    
    .popup-cancel {
      height: 100rpx;
      display: flex;
      justify-content: center;
      align-items: center;
      border-top: 16rpx solid #F9F9F9;
      
      text {
        font-size: 32rpx;
        color: #000000;
      }
    }
  }
}
</style>