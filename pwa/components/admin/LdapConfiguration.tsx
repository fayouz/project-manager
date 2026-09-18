import React from 'react';
import {
    List,
    Datagrid,
    TextField,
    BooleanField,
    Edit,
    SimpleForm,
    TextInput,
    BooleanInput,
    NumberInput,
    PasswordInput,
    useNotify,
} from 'react-admin';
import { Button } from '@mui/material';
import { useFormContext } from 'react-hook-form';

const TestLdapButton = () => {
    const notify = useNotify();
    const { getValues } = useFormContext();

    const handleTest = async () => {
        const values = getValues();
        try {
            const token = localStorage.getItem('token');
            
            const response = await fetch('/api/ldap_configurations/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': token ? `Bearer ${token}` : '',
                },
                body: JSON.stringify({
                    host: values.host,
                    port: values.port,
                    baseDn: values.baseDn,
                    bindDn: values.bindDn,
                    bindPassword: values.bindPassword,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                notify(data.message, { type: 'success' });
            } else {
                notify(data.message || 'Erreur lors du test', { type: 'error' });
            }
        } catch (error) {
            notify('Erreur réseau lors du test', { type: 'error' });
        }
    };

    return (
        <Button onClick={handleTest} color="primary" variant="contained" style={{ marginTop: '1em', marginBottom: '1em' }}>
            Tester la connexion
        </Button>
    );
};

export const LdapConfigList = () => (
    <List title="Configuration LDAP">
        <Datagrid rowClick="edit">
            <TextField source="id" />
            <BooleanField source="enabled" label="Activé" />
            <TextField source="host" label="Hôte" />
            <TextField source="port" label="Port" />
            <TextField source="baseDn" label="Base DN" />
        </Datagrid>
    </List>
);

export const LdapConfigEdit = () => (
    <Edit title="Modifier la configuration LDAP">
        <SimpleForm>
            <BooleanInput source="enabled" label="Activé" />
            <TextInput source="host" label="Hôte" fullWidth />
            <NumberInput source="port" label="Port" />
            <TextInput source="baseDn" label="Base DN" fullWidth />
            <TextInput source="bindDn" label="Compte de service (DN)" fullWidth />
            <PasswordInput source="bindPassword" label="Mot de passe du compte de service" fullWidth />
            <TestLdapButton />
        </SimpleForm>
    </Edit>
);
