<template>
  <div class="cursor-pointer flex gap-1">
    <template v-if="edit == true">
      <input v-model="newValue" />
      <button @click="save()" class="cursor-pointer">v</button>
    </template>
    <p v-else @click="editValue">{{ value }}</p>
  </div>
</template>
<script setup>
import { ref, computed, defineModel } from "vue";
const edit = ref(false);
const model = defineModel();
const value = computed(() => model.value.join(", "));
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
