<template>
    <div>
        <div class="row col">
            <h1>Projects</h1>
        </div>

        <div class="row col">
            <form>
                <div class="form-row">
                    <div class="col-8">
                        <input
                                v-model="name"
                                type="text"
                                class="form-control"
                        >
                    </div>
                    <div class="col-4">
                        <button
                                :disabled="name.length === 0 || isLoading"
                                type="button"
                                class="btn btn-primary"
                                @click="createProject()"
                        >
                            Create
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div v-if="isLoading" class="row col">
            <p>Loading...</p>
        </div>

        <div v-else-if="hasError"
                class="row col">
            <div class="alert alert-danger" role="alert">
                {{ error }}
            </div>
        </div>

        <div v-else-if="!hasProjects" class="row col">
            No projects!
        </div>

        <div v-else class="row col      ">
            <b-card-group v-for="project in projects" :key="project.id" deck>

                    <project :name="project.name" :summary="project.summary" :id="project.id"/>

            </b-card-group>

        </div>

    </div>
</template>

<script>
    import Project from "../components/Project";

    export default {
        name: "Projects",
        components: {
            Project
        },
        data() {
            return {
                name: ""
            };
        },
        computed: {
            isLoading() {
                return this.$store.getters["project/isLoading"];
            },
            hasError() {
                return this.$store.getters["project/hasError"];
            },
            error() {
                return this.$store.getters["project/error"];
            },
            hasProjects() {
                return this.$store.getters["project/hasProjects"];
            },
            projects() {
                return this.$store.getters["project/projects"];
            }
        },
        created() {
            this.$store.dispatch("project/findAll");
        },
        methods: {
            async createProject() {
                const result = await this.$store.dispatch("project/create", this.$data.name);
                if (result !== null) {
                    this.$data.name = "";
                    this.$data.summary = "";
                }
            }
        }
    };
</script>