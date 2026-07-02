<!-- CustomRichContent.vue -->
<template>
  <view class="custom-rich-content">
    <block v-for="(item, index) in parsedContent" :key="index">
      <video 
        v-if="item.type === 'video'" 
        :src="item.src"  
        controls 
        class="content-video">
      </video>
      <image 
        v-else-if="item.type === 'image'" 
        :lazy-load="true" 
        :src="item.src" 
        mode="widthFix" 
        class="content-image">
      </image>
      <rich-text 
        v-else-if="item.type === 'text'" 
        :nodes="item.content" 
        class="content-text">
      </rich-text>
    </block>
  </view>
</template>

<script>
export default {
  name: 'CustomRichContent',
  props: {
    content: {
      type: String,
      default: ''
    }
  },
  computed: {
    parsedContent() {
      const result = [];
      if (!this.content) return result;
      
      const content = this.content;
      
      // 调试：打印原始内容
      console.log('原始内容:', content);
      
      // 使用正则表达式匹配视频和图片标签
      const videoRegex = /<video[^>]*src="([^"]*)"[^>]*><\/video>/g;
      const imageRegex = /<img[^>]*src="([^"]*)"[^>]*\/?>/g;
      
      // 创建一个包含所有媒体元素的数组，按位置排序
      const mediaElements = [];
      
      let match;
      
      // 收集所有视频元素
      while ((match = videoRegex.exec(content)) !== null) {
        mediaElements.push({
          type: 'video',
          src: match[1],
          start: match.index,
          end: match.index + match[0].length,
          fullMatch: match[0]
        });
      }
      
      // 重置正则表达式
      imageRegex.lastIndex = 0;
      
      // 收集所有图片元素
      while ((match = imageRegex.exec(content)) !== null) {
        mediaElements.push({
          type: 'image',
          src: match[1],
          start: match.index,
          end: match.index + match[0].length,
          fullMatch: match[0]
        });
      }
      
      // 按位置排序
      mediaElements.sort((a, b) => a.start - b.start);
      
      let lastIndex = 0;
      
      // 按顺序处理每个媒体元素
      for (const element of mediaElements) {
        // 添加媒体元素前的文本
        if (element.start > lastIndex) {
          const textContent = content.substring(lastIndex, element.start);
          if (textContent.trim()) {
            result.push({
              type: 'text',
              content: this.processTextContent(textContent)
            });
          }
        }
        
        // 添加媒体元素
        result.push({
          type: element.type,
          src: element.src
        });
        
        lastIndex = element.end;
      }
      
      // 添加剩余的文本
      if (lastIndex < content.length) {
        const remainingText = content.substring(lastIndex);
        if (remainingText.trim()) {
          result.push({
            type: 'text',
            content: this.processTextContent(remainingText)
          });
        }
      }
      
      // 如果没有媒体元素，整个内容都是文本
      if (result.length === 0 && content.trim()) {
        result.push({
          type: 'text',
          content: this.processTextContent(content)
        });
      }
      
      // 调试：打印解析结果
      console.log('解析结果:', result);
      
      return result;
    }
  },
  methods: {
    // 处理文本内容，将换行符转换为br标签
    processTextContent(text) {
      if (!text) return '';
      // 将回车符、换行符转换为br标签
      return text.replace(/\r?\n/g, '<br>');
    }
  }
}
</script>

<style>
.custom-rich-content {
  width: 100%;
  box-sizing: border-box;
}

.content-video {
  width: 100%;
  margin: 10rpx 0;
}

.content-image {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 10rpx 0;
}

.content-text {
  width: 100%;
  line-height: 1.6;
}
</style>