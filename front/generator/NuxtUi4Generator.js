import handlebars from "handlebars";
import hbh_comparison from "handlebars-helpers/lib/comparison.js";
import hbh_array from "handlebars-helpers/lib/array.js";
import hbh_string from "handlebars-helpers/lib/string.js";
import chalk from "chalk";
import path from "path";
import { fileURLToPath } from "url";
import BaseGenerator from "../node_modules/@api-platform/create-client/lib/generators/BaseGenerator.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default class NuxtUi4Generator extends BaseGenerator {
  constructor(params) {
    super(params);

    this.templateDirectory = path.resolve(__dirname, "templates");

    this.registerTemplates("common/", [
      // types
      "types/collection.ts",
      "types/error.ts",
      "types/foo.ts",
      "types/item.ts",
      "types/view.ts",
      // utils
      "utils/config.ts",
      "utils/date.ts",
      "utils/error.ts",
      "utils/mercure.ts",
    ]);

    this.registerTemplates("vue-common/", [
      // composables
      "composables/mercureItem.ts",
      "composables/mercureList.ts",
    ]);

    this.registerTemplates("nuxtui4/", [
      // common components
      "components/common/FormRepeater.vue",
      // components
      "components/foo/FooCreate.vue",
      "components/foo/FooForm.vue",
      "components/foo/FooList.vue",
      "components/foo/FooShow.vue",
      "components/foo/FooUpdate.vue",
      // pages
      "pages/foos/[id]/index.vue",
      // composables
      "composables/api.ts",
      // stores
      "stores/foo/create.ts",
      "stores/foo/delete.ts",
      "stores/foo/list.ts",
      "stores/foo/show.ts",
      "stores/foo/update.ts",
      // types
      "types/api.ts",
      // utils
      "utils/resource.ts",
    ]);

    handlebars.registerHelper("compare", hbh_comparison.compare);
    handlebars.registerHelper("forEach", hbh_array.forEach);
    handlebars.registerHelper("lowercase", hbh_string.lowercase);
  }

  help(resource) {
    console.log(
      chalk.green('Nuxt UI v4 CRUD for "%s" generated successfully!'),
      resource.title
    );
  }

  getContextForResource(resource) {
    const lc = resource.title.toLowerCase();
    const titleUcFirst =
      resource.title.charAt(0).toUpperCase() + resource.title.slice(1);
    const fields = this.parseFields(resource);
    const hasIsRelation = fields.some((field) => field.isRelation);
    const hasIsRelations = fields.some((field) => field.isRelations);
    const hasRelations = hasIsRelation || hasIsRelations;
    const formFields = this.buildFields(fields);

    return {
      title: resource.title,
      name: resource.name,
      lc,
      uc: resource.title.toUpperCase(),
      fields,
      hasIsRelation,
      hasIsRelations,
      hasRelations,
      formFields,
      hydraPrefix: this.hydraPrefix,
      titleUcFirst,
    };
  }

  generate(api, resource, dir) {
    const context = this.getContextForResource(resource);
    const { lc, titleUcFirst } = context;

    [
      `${dir}/components`,
      `${dir}/components/common`,
      `${dir}/components/${lc}`,
      `${dir}/composables`,
      `${dir}/pages`,
      `${dir}/pages/${lc}s`,
      `${dir}/pages/${lc}s/[id]`,
      `${dir}/stores`,
      `${dir}/stores/${lc}`,
      `${dir}/types`,
      `${dir}/utils`,
    ].forEach((d) => this.createDir(d, false));

    [
      // components
      "components/%s/%sCreate.vue",
      "components/%s/%sForm.vue",
      "components/%s/%sList.vue",
      "components/%s/%sShow.vue",
      "components/%s/%sUpdate.vue",
      // pages
      "pages/%ss/[id]/index.vue",
      // stores
      "stores/%s/create.ts",
      "stores/%s/delete.ts",
      "stores/%s/list.ts",
      "stores/%s/show.ts",
      "stores/%s/update.ts",
      // types
      "types/%s.ts",
    ].forEach((pattern) => {
      this.createFileFromPattern(
        pattern,
        dir,
        [lc, titleUcFirst],
        context,
        ["foo", "Foo"]
      );
    });

    [
      // components
      "components/common/FormRepeater.vue",
      // composables
      "composables/api.ts",
      "composables/mercureItem.ts",
      "composables/mercureList.ts",
      // types
      "types/api.ts",
      "types/collection.ts",
      "types/error.ts",
      "types/item.ts",
      "types/view.ts",
      // utils
      "utils/date.ts",
      "utils/error.ts",
      "utils/mercure.ts",
      "utils/resource.ts",
    ].forEach((pathStr) => {
      this.createFile(pathStr, `${dir}/${pathStr}`, context, false);
    });

    // config
    this.createConfigFile(`${dir}/utils/config.ts`, {
      entrypoint: api.entrypoint,
    });
  }

  parseFields(resource) {
    const fields = [
      ...(resource.writableFields || []),
      ...(resource.readableFields || []),
    ].reduce((list, field) => {
      if (list[field.name]) {
        return list;
      }

      const isReferences = Boolean(field.reference && field.maxCardinality !== 1);
      const isEmbeddeds = Boolean(field.embedded && field.maxCardinality !== 1);

      return {
        ...list,
        [field.name]: {
          ...field,
          readonly: false,
          isReferences,
          isEmbeddeds,
          isRelation: Boolean(field.reference || field.embedded),
          isRelations: isEmbeddeds || isReferences,
        },
      };
    }, {});

    return Object.values(fields);
  }
}
