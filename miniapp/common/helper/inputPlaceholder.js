export default {
  data() {
    return {
      focusedInputKey: "",
    };
  },
  methods: {
    focusField(key) {
      this.focusedInputKey = key;
    },
    blurField(key) {
      if (!key || this.focusedInputKey === key) {
        this.focusedInputKey = "";
      }
    },
    placeholderFor(key, text) {
      return this.focusedInputKey === key ? "" : text;
    },
  },
};
