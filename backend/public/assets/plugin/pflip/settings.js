var option_PDFF = {

 

   /* BASIC SETTINGS */  

    openPage: 1,

    height: '100%',

    enableSound: true,

    downloadEnable: true, 

    direction: pdfflip.DIRECTION.LTR,

    autoPlay: true,

    autoPlayStart: false,

    autoPlayDuration: 3000,

    autoEnableOutline: false,

    autoEnableThumbnail: false,





	/* TRANSLATE INTERFACE */  

 

    text: {

      toggleSound: "音乐",

      toggleThumbnails: "缩略图",

      toggleOutline: "目录",

      previousPage: "上一页",

      nextPage: "下一页",

      toggleFullscreen: "全屏",

      zoomIn: "放大",

      zoomOut: "缩小",

      downloadPDFFile: "下载PDF",

      gotoFirstPage: "首页",

      gotoLastPage: "尾页",

      play: "自动播放",

      pause: "手动播放",

      share: "分享"

    },



	/* ADVANCED SETTINGS */  



    hard: "none",

    overwritePDFOutline: true,

    duration: 1000,

    pageMode: pdfflip.PAGE_MODE.AUTO,

    singlePageMode: pdfflip.SINGLE_PAGE_MODE.AUTO,

	transparent: false,

	scrollWheel: true,

    zoomRatio: 1.5,

	maxTextureSize: 1600,

	backgroundImage: "background.jpg",

    backgroundColor: "#fff",

    controlsPosition: pdfflip.CONTROLSPOSITION.BOTTOM,

    allControls: "thumbnail,play,startPage,altPrev,pageNumber,altNext,endPage,zoomIn,zoomOut,fullScreen,sound,share",

    hideControls: "outline,download",



};



var pdfflipLocation = "./pflip/";