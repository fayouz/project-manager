import { HydraAdmin, Resource } from "@api-platform/admin";
import { LdapConfigList, LdapConfigEdit } from "./LdapConfiguration";

const App = () => (
  <HydraAdmin
    entrypoint={window.origin}
    title="API Platform admin"
  >
    <Resource name="ldap_configurations" list={LdapConfigList} edit={LdapConfigEdit} options={{ label: 'LDAP Configuration' }} />
  </HydraAdmin>
);

export default App;
