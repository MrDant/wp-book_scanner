<template>
  <div class="flex gap-1 flex-wrap">
    <template v-if="!edit">
      <span v-if="!model && empty" @click="editValue">{{ empty }}</span>
      <span v-else v-html="model" @click="editValue" class="min-h-10 w-full" />
    </template>
    <template v-else>
      <template v-if="type == 'textarea'">
        <textarea v-model="newValue" class="w-full" @input="auto_grow" />
      </template>
      <template v-else>
        <input v-model="newValue" :type="type" />
      </template>
      <button @click="edit = false" class="danger">x</button>
      <button
        @click="
          model = newValue;
          edit = false;
        "
      >
        v
      </button>
    </template>
  </div>
</template>
<script setup>
import { ref, defineModel, defineProps } from "vue";
const model = defineModel();
const { type, empty } = defineProps({
  type: { default: "text" },
  empty: {},
});
const newValue = ref("");
const edit = ref(false);

function auto_grow(e) {
  const element = e.target;
  element.style.height = "5px";
  element.style.height = element.scrollHeight + "px";
}

function editValue() {
  newValue.value = model.value;
  edit.value = true;
}
</script>

<style scoped>
textarea {
  resize: none;
  overflow: hidden;
  min-height: 50px;
}
</style>
