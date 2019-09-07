<template>
    <div>
        <div class="row col">
            <h1>Projects</h1>
        </div>

        <h1 id="projectTitle">{{ project.name}}</h1>

    </div>
</template>

<script>
    import Project from "../components/Project";

    export default {
        name: "Project",
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
            },
            project() {
                return this.$store.getters["project/project"];
            }
        },
        created() {
            this.$store.dispatch("project/find", this.$route.params.id);
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