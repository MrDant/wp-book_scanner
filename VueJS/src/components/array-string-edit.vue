<template>
  <div class="cursor-pointer flex gap-1 min-h-3">
    <template v-if="edit == true">
      <input v-model="newValue" />
      <button @click="save()" class="cursor-pointer">v</button>
    </template>
    <template v-else>
      <p v-if="!value" @click="editValue">{{ empty }}</p>
      <p v-else @click="editValue">{{ value }}</p>
    </template>
  </div>
</template>
<script setup>
import { ref, computed, defineModel, defineProps } from "vue";
const edit = ref(false);
const model = defineModel();
const { empty } = defineProps(["empty"]);
const value = computed(() => {
  return Array.isArray(model.value) ? model.value.join(", ") : model.value;
});
const newValue = ref("");

function editValue() {
  newValue.value = value.value;
  edit.value = true;
}

function save() {
  model.value = newValue.value.split(",").map((e) => e.trim());
  edit.value = false;
}
</script>
