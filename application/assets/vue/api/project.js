import axios from "axios";

export default {
    create(name) {
        return axios.post("/api/projects", {
            name: name
        });
    },
    findAll() {
        return axios.get("/api/projects");
    },
    find(id) {
        return axios.get("/api/projects/"+id);
    }
};