<template>
	<view>
		<web-view :src="linkurl"></web-view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				linkurl: ""
			};
		},
		onLoad(option) {
			this.linkurl = option.link
		}
	}
</script>

<style lang="scss">

</style>
