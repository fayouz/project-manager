<template>
    <b-card no-body title="title" v-bind:title="name" img-src="https://picsum.photos/300/300/?image=41" img-alt="Image"
            img-top>
        <b-card-body>
            <b-link v-bind:to="'/projects/'+id">
                <h4>{{ name }}</h4>
            </b-link>
            <b-card-text>
                {{ summary }}
            </b-card-text>
        </b-card-body>
        <template v-slot:footer>
            <small class="text-muted">Last updated 3 mins ago</small>
        </template>
    </b-card>


</template>

<script>
    export default {
        name: "Project",
        props: {
            id: {
                type: Number,
                required: true
            },
            name: {
                type: String,
                required: true
            },
            summary: {
                type: String,
                required: false
            }
        }
    };
</script>