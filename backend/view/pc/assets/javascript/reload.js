// 保存原生方法
const nativeElementQuerySelector = Element.prototype.querySelector;
const nativeDocumentQuerySelector = Document.prototype.querySelector;
function ytCustomQuerySelector(selector) {
    // 第二步：尝试用选择器获取DOM元素
    // 执行原生选择器查询
    const foundElement = this === document ?
        nativeDocumentQuerySelector.call(this, selector) :
        nativeElementQuerySelector.call(this, selector);

    if (foundElement) {
        // 设置属性
        if (!foundElement.hasAttribute('data-selectorname')) {
            foundElement.setAttribute('data-selectorname', selector);
        }
        // 第三步：直接返回找到的元素
        return foundElement;
    }

    // 如果通过选择器没找到，尝试通过data-selectorName属性查找
    const allElements = document.querySelectorAll('[data-selectorname]');
    for (let i = 0; i < allElements.length; i++) {
        if (allElements[i].getAttribute('data-selectorname') === selector) {
            return allElements[i];
        }
    }

    // 如果都没找到，返回null
    return null;
}

// 如果需要也重写querySelectorAll，可以类似实现
// 重写原生的querySelector
Document.prototype.querySelector = ytCustomQuerySelector
Element.prototype.querySelector = ytCustomQuerySelector

const nativeElementInsertBefore = Element.prototype.insertBefore;

function ytCustomInsertBefore(newNode, referenceNode) {
    // 当前元素作为默认父元素
    const defaultParentNode = this;

    // 检查参考节点是否存在
    if (!referenceNode) {
        // 如果没有提供参考节点，直接添加到末尾
        return nativeElementInsertBefore.call(defaultParentNode, newNode, null);
    }

    // 检查参考节点是否仍然是父节点的直接子节点
    if (referenceNode.parentNode === defaultParentNode) {
        // 正常情况：参考节点仍在父节点下，直接插入
        return nativeElementInsertBefore.call(defaultParentNode, newNode, referenceNode);
    }

    // 检查参考节点是否有 data-ytparentvalue 属性（被移动出去的节点）
    const referenceParentValue = referenceNode.getAttribute('data-ytparentvalue');

    if (referenceParentValue) {
        // 查找具有匹配 data-ytextravalue 的父元素
        const actualParentNode = document.querySelector('[data-ytextravalue="' + referenceParentValue + '"]');

        if (actualParentNode) {
            // 获取参考节点原来的索引位置
            const originalIndex = referenceNode.getAttribute('data-ytoriginindex');

            if (originalIndex !== null && !isNaN(originalIndex)) {
                // 获取实际父节点当前的所有子节点
                const children = Array.from(actualParentNode.children);

                // 查找应该插入的位置
                for (let i = 0; i < children.length; i++) {
                    const child = children[i];
                    const childOriginalIndex = child.getAttribute('data-ytoriginindex');

                    // 如果子节点有原始索引，并且比参考节点的原始索引大
                    if (childOriginalIndex !== null && !isNaN(childOriginalIndex)) {
                        if (parseInt(childOriginalIndex) > parseInt(originalIndex)) {
                            // 找到第一个索引更大的节点，插入到它前面
                            return nativeElementInsertBefore.call(actualParentNode, newNode, child);
                        }
                    }
                }

                // 如果没有找到更大的索引，插入到最后
                return nativeElementInsertBefore.call(actualParentNode, newNode, null);
            }

            // 没有原始索引信息，插入到实际父元素的最后
            return nativeElementInsertBefore.call(actualParentNode, newNode, null);
        }
    }

    // 默认情况：插入到当前父元素的最后
    return nativeElementInsertBefore.call(defaultParentNode, newNode, null);
}

// 重写原生 insertBefore 方法
Element.prototype.insertBefore = ytCustomInsertBefore;

// 需要给新添加的a标签跳转链接加入一些必要的样式 保证加入后不影响原来的布局
function addUniqueStyle(cssText, id = 'custom-style') {
    const targetDom = document.getElementById(id)
    if (targetDom && targetDom.tagName === 'STYLE') return; // 已存在则跳过

    const style = document.createElement('style');
    style.id = id;
    style.innerHTML = cssText;
    document.head.appendChild(style);
}
addUniqueStyle('.yt-a-defalut-link[custom-a="true"]::before, .yt-a-defalut-link[custom-a="true"]::after {content: none};.yt-a-defalut-link[custom-a="true"] > * { margin:0;flex:1; }')

// 定义要劫持的属性
const ytCustomProperties = ['textContent', 'innerText'];

ytCustomProperties.forEach(prop => {
    let descriptor = Object.getOwnPropertyDescriptor(Element.prototype, prop) ||
        Object.getOwnPropertyDescriptor(Node.prototype, prop);
    if (descriptor && descriptor.set && descriptor.get) {
        const originalGet = descriptor.get; // 保存原生 getter
        const originalSet = descriptor.set;
        Object.defineProperty(Element.prototype, prop, {
            get: function () {
                return originalGet.call(this); // 保持原生 getter 逻辑
            },
            set: function (value) {
                // 优先取 data-yteditvalue，否则用传入的 value
                const finalValue = this.dataset.yteditvalue ?? value;
                originalSet.call(this, finalValue);
            },
            configurable: true,
        });
    }
});

function ytCustomLinkNavigation () {
    const parseWithURLSearchParams = (queryString) => {
        const params = queryString.split('&')
        const result = {}
        params.forEach(param => {
            const key = param.split('=')[0]
            const value = param.split('=')[1]
            result[key] = value
        })
        return result
    }
    const topWin = window.top
    const originParams = parseWithURLSearchParams(topWin.document.location.href.split('?')[1])
    const href = window.event.currentTarget.getAttribute('custom-href')
    if (href) {
        if (topWin.location.hash.startsWith('#/edit?')) {
            const { uuid } = parseWithURLSearchParams(href)
            const event = new CustomEvent('changePage', {
            detail: { uuid },
            bubbles: true,
            cancelable: true
            });
            topWin.dispatchEvent(event)
        } else {
            const newParams = parseWithURLSearchParams(href)
            const event = new CustomEvent('previewPageVersion', {
            detail: {
                appuuid: newParams?.appuuid,
                pageUuid: newParams?.uuid,
                callback: (version) => {
                const params = Object.keys(originParams).reduce((acc, key) => {
                    if (acc !== '?') {
                    acc+='&'
                    }
                    if (key === 'version') {
                    acc+=key+'='+version
                    } else {
                    acc+=key+'='+newParams[key]
                    }
                    return acc
                }, '?')
                topWin.location.href = topWin.document.location.href.split('?')[0] + params
                }
            },
            bubbles: true,
            cancelable: true
            });
            topWin.dispatchEvent(event)
        }
    }
}

function ytCustomClickRouter (element, event) {
    if (event) {
        event.stopPropagation();
    }
    document.querySelectorAll('.active-menu').forEach(item => {
        item.classList.remove('active-menu');
    });
    element.classList.add('active-menu');
    window.top.postMessage(element.getAttribute('data-uuid'), '*');
}

function ytGetIframeResize (iframe) {
  window.addEventListener('message', function(e) {
    // 确保消息来自可信源
    if (e.data && e.data.type === 'iframe-resize') {
      iframe.style.height = e.data.height + 'px';
      const style = window.getComputedStyle(iframe.parentElement)
      //   导航页在左侧时，iframe超出部分被隐藏了
      if (style.overflow === 'hidden') {
        iframe.parentElement.style.overflow = 'auto'
      } else if (iframe.parentElement.nodeName !== 'MAIN') {  // app页面，页面高度超出隐藏时，内容需要出现滚动条
        iframe.parentElement.style.overflow = 'auto'
      }
    }
  })
}

function ytSendPageSize () {
    function hasHorizontalScrollbar() {
        // 检查html元素
        const html = document.documentElement;
        if (html.scrollWidth > html.clientWidth) return true;
        // 检查body元素
        const body = document.body;
        if (body.scrollWidth > body.clientWidth) return true;
        return false;
    }

    function sendHeightToParent(e) {
        ytSendPageSizeBefor()
        let height = Math.max(
            document.body.scrollHeight,
            document.body.offsetHeight,
            document.documentElement.clientHeight,
            document.documentElement.scrollHeight,
            document.documentElement.offsetHeight
        );
        // iframe如果有横向滚动条，需要加上横向滚动条的高度
        if (hasHorizontalScrollbar()) {
            height += 20 
        }
        
        window.parent.postMessage({
            type: 'iframe-resize',
            height: height
        }, '*'); // 生产环境应替换为具体的父页面域名
        showIframe()
    }

    function loadPage (e) {
        window.scrollTo(0, 800);
        setTimeout(()=>{
          sendHeightToParent(e)
          // 如果内容动态加载，可能需要MutationObserver
          const observer = new MutationObserver(sendHeightToParent);
          observer.observe(document.body, {
            attributes: true,
            childList: true,
            subtree: true,
            characterData: true
          });
        }, 300)
    }
    hideIframe()
    loadPage()
}

// 解决滚动条初始滚动能被看到的问题
function showIframe () {
    try {
        const iframe = window.parent?.document?.querySelector('iframe')
        iframe.style.visibility = 'visible'
    } catch (error) {
      console.log(error)
    }
}
function hideIframe () {
    try {
        const iframe = window.parent?.document?.querySelector('iframe')
        iframe.style.visibility = 'hidden'
    } catch (error) {
      console.log(error)
    }
}

function ytSendPageSizeBefor () {
   // 获取当前视口高度
  const vh = window.innerHeight / 100;
  const allElements = document.body.querySelectorAll('*');
  
  allElements.forEach(el => {
    // 排除特定元素
    if (el.classList.contains('no-vh-convert')) {
      return;
    }
    // 内联样式（优先级最高）
    if (el.style.height && el.style.height.includes('vh')) {
      const value = parseFloat(el.style.height);
      el.style.height = `${value * vh}px`;
    }

    // 计算样式（包括 Tailwind 普通类名如 h-screen）
    const computedHeight = getComputedStyle(el).height;
    if (computedHeight.endsWith('vh')) {
      el.style.height = `${parseFloat(computedHeight) * vh}px`;
    }
  });

  // 2. 专门处理 Tailwind 任意值类名（如 h-[70vh]）
  const vhClassRegex = /^(sm:|md:|lg:|xl:|2xl:)?h-\[(\d+)vh\]$/;
  allElements.forEach(el => {
    // 排除特定元素
    if (el.classList.contains('no-vh-convert')) {
      return;
    }
    const classList = Array.from(el.classList);
    let modified = false;
    classList.forEach(className => {
      const match = className.match(vhClassRegex);
      if (match) {
       // 提取断点前缀和vh值
        const breakpoint = match[1] || '';
        const vhValue = parseInt(match[2]);
        
        // 计算px值并四舍五入
        const pxValue = vhValue * vh;
        
        // 创建新的px类名
        const newClassName = `${breakpoint}h-[${pxValue}px]`;
        
        // 移除旧类名，添加新类名
        el.classList.remove(className);
        el.classList.add(newClassName);
        
        modified = true;
        console.log(`Converted ${className} to ${newClassName} on`, el);
      }
    });
    // 如果元素类名被修改，强制重绘
    if (modified) {
      el.style.display = 'none';
      el.offsetHeight; // 触发重排
      el.style.display = '';
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
    /* 2. 监听父页面指令 */
    window.addEventListener('message', e => {
    if (e.data && e.data.cmd === 'setInnerSrc') {
        const inner = document.getElementById('navigation');
        if( !inner ) {
            return;
        }
        inner.removeAttribute('srcdoc');
        inner.src = e.data.url;          // 改成 B.html

        /* 3. 等 B 加载完，把消息接力传回父页面 */
        inner.onload = () => {
        window.parent.postMessage({ cmd: 'bLoaded' }, '*');
        };
    }
    if (e.data && e.data.text) {
        document.getElementById('box').textContent = e.data.text;
    }
    });
    const navIframe = document.getElementById('navigation') || document.getElementById('contentFrame')
    if (navIframe) {
        const top =  navIframe.getBoundingClientRect().top
        if (top) {
            navIframe.style.height = `calc(100vh - ${top}px)`; // 减去导航高度
        }
    }
});
