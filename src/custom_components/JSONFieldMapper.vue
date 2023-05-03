<template>
    <div class='jsonfieldmapper'>
        <template v-for="(field, index) in dataFields" align-v>
            <validation-observer :key="refBase+'-'+index.toString()" v-slot="{ validate }" slim>
                <b-row>
                    <!-- Type -->
                    <b-col>
                        <validation-provider rules="required" vid="type" name="type" v-slot="{ errors }">
                            <b-form-select 
                                v-model="field.type"
                                size="sm"
                                vid="type"
                                name="type"
                                @change="changeFieldRowType(field)">
                                <b-form-select-option :value="null" disabled>Field Type</b-form-select-option>
                                <b-form-select-option v-if="parent" value="text">Text</b-form-select-option>
                                <b-form-select-option v-if="parent" value="number">Number</b-form-select-option>
                                <b-form-select-option v-if="parent" value="bool">Boolean</b-form-select-option>
                                <b-form-select-option value="array">Array</b-form-select-option>
                                <b-form-select-option value="object">Object</b-form-select-option>
                            </b-form-select>
                            <span class='validation-failed'>{{ errors[0] }}</span>
                        </validation-provider>
                    </b-col>
                    <!-- Source -->
                    <b-col>
                        <validation-provider

                            v-if="field.type ? (
                                field.type == 'array' ? (
                                    parent ? (
                                        parent.type == 'array' ? false : true
                                    ) : false
                                ) : field.type == 'object' ? (
                                    parent ? (
                                        parent.type == 'array' ? false : true
                                    ) : false
                                ) : true 
                            ) : false" 

                            rules="required"
                            vid="source"
                            name="source"
                            v-slot="{ errors }">
                            <b-form-input
                                v-model="field.source"
                                size="sm"
                                vid="source"
                                name="source"
                                placeholder="Source Field">
                            </b-form-input>
                            <span class='validation-failed'>{{ errors[0] }}</span>
                        </validation-provider>
                    </b-col>
                    <!-- Target -->
                    <b-col>
                        <validation-provider
                            v-if="field.source && field.type && ['array','object'].indexOf(field.type) == -1"
                            rules="required"
                            vid="target"
                            name="target"
                            v-slot="{ errors }">
                            <b-form-select
                                v-model="field.target"
                                size="sm"
                                vid="target"
                                name="target"
                                :options="targets">
                                <template #first>
                                    <b-form-select-option value="null" disabled>Select Target</b-form-select-option>
                                </template>
                            </b-form-select>
                            <span class='validation-failed'>{{ errors[0] }}</span>
                        </validation-provider>
                    </b-col>
                    <!-- Controls -->
                    <b-col class='text-right'>
                        <b-button-group size="sm">
                            <b-button
                                title="Delete Row"
                                variant="primary-outline"
                                @click="delFieldRow(index)"
                                :disabled="dataFields.length < 2">
                                <b-icon icon="dash-circle" aria-hidden="true"></b-icon>
                            </b-button>
                            <b-button
                                :title="(parent && parent.type == 'array' && parent.config.variable_members == true) ? 'Add Variation Below' : 'Add Row Below'"
                                variant="primary-outline"
                                @click="addFieldRow(index, validate)"
                                :disabled="!parent || (parent.type == 'array' && parent.config.variable_members == false)">
                                <b-icon 
                                    :icon="(parent && parent.type == 'array' && parent.config.variable_members == true) ? 'plus-circle-fill' : 'plus-circle'"
                                    aria-hidden="true">
                                </b-icon>
                            </b-button>
                            <b-button
                                title="Move Up"
                                variant="primary-outline"
                                @click="moveUpFieldRow(index)"
                                :disabled="!parent || (parent.type == 'array' && parent.config.variable_members == false)">
                                <b-icon icon="arrow-up-circle" aria-hidden="true"></b-icon>
                            </b-button>
                            <b-button
                                title="Move Down"
                                variant="primary-outline"
                                @click="moveDownFieldRow(index)"
                                :disabled="!parent || (parent.type == 'array' && parent.config.variable_members == false)">
                                <b-icon icon="arrow-down-circle" aria-hidden="true"></b-icon>
                            </b-button>
                            <b-button
                                title="Field Config"
                                variant="primary-outline"
                                @click="openFieldConfigDialog(field, index)"
                                :disabled="!field.type">
                                <b-icon :icon="field.logic.length ? 'gear-fill' : 'gear'" aria-hidden="true"></b-icon>
                            </b-button>
                        </b-button-group>
                    </b-col>
                </b-row>

                <b-row v-if="field.children && field.children.length">
                    <b-col>
                        <!-- field.children -> dataFields -->
                        <JSONFieldMapper
                            :parentObj="thisNode"
                            :parent="field"
                            :meta="meta"
                            :fields="field.children"
                            :targets="targets"
                            :refBase="refBase+'-'+index.toString()">
                        </JSONFieldMapper>
                    </b-col>
                </b-row>
            </validation-observer>

            <b-modal
                :id="refBase+'-modal'"
                body-class="jsonfieldmapper_props"
                size="lg"
                v-model="showFieldConfigDialog"
                centered
                no-close-on-esc
                no-close-on-backdrop>

                <template #modal-header="{ close }">
                    <h6 slot="header" class="modal-title" id="modal-title-default">
                        Field Configuration: {{ cfg_field ? (cfg_field.source ? cfg_field.source : cfg_field.type) : "" }}
                    </h6>
                </template>

                <template #default="{ hide }">
                    <template v-if="cfg_field">
                        <b-card :title="cfg_field.type.charAt(0).toUpperCase() + cfg_field.type.slice(1) + ' Field Properties'">
                            <b-card-text>

                                <!-- Per Field Property Configuration Fields -->

                                <template v-if="cfg_field.type == 'array'">
                                    <b-row>
                                        <b-col md='6'>
                                            <b-form-group
                                                label="Variable Members?"
                                                label-class="form-control-label"
                                                description="Specify whether this array contains members with variable layouts (objects only).">
                                                <b-form-checkbox v-model="cfg_field.config.variable_members" :value="true" :unchecked-value="false">
                                                Variable Members
                                                </b-form-checkbox>
                                            </b-form-group>
                                        </b-col>
                                        <b-col md='6'>
                                            <b-form-group
                                                label="Creation Point"
                                                label-class="form-control-label"
                                                description="Designate this array as the starting point for item creation">
                                                <b-form-checkbox v-model="cfg_field.config.creation_point" :value="true" :unchecked-value="false">
                                                Creation Point
                                                </b-form-checkbox>
                                            </b-form-group>
                                        </b-col>
                                    </b-row>
                                </template>

                                <template v-else-if="cfg_field.type == 'object'">
                                    <div>No addtional properties to configure</div>
                                </template>

                                <template v-else-if="cfg_field.type == 'text'">
                                    <b-row>
                                        <b-col md='6'>
                                            <b-form-group
                                                label="Offset"
                                                label-class="form-control-label"
                                                description="The number of characters to skip (from the start of the field)">
                                                <b-form-spinbutton size="sm" v-model="cfg_field.config.offset" min="0"></b-form-spinbutton>
                                            </b-form-group>
                                        </b-col>
                                        <b-col md='6'>
                                            <b-form-group
                                                label="Limit"
                                                label-class="form-control-label"
                                                description="The number of characters to read (after the offset). Specify 0 to read all.">
                                                <b-form-spinbutton size="sm" v-model="cfg_field.config.limit" min="0"></b-form-spinbutton>
                                            </b-form-group>
                                        </b-col>
                                    </b-row>

                                </template>

                                <template v-else-if="cfg_field.type == 'number'">
                                    <b-row>
                                        <b-col md='6'>
                                            <b-form-group
                                                label="Format"
                                                label-class="form-control-label"
                                                description="Specify the number format">
                                                <b-form-select 
                                                    v-model="cfg_field.config.format"
                                                    size="sm">
                                                    <b-form-select-option :value="null" disabled>Field Type</b-form-select-option>
                                                    <b-form-select-option value="int">Integer</b-form-select-option>
                                                    <b-form-select-option value="float" selected>Floating Point</b-form-select-option>
                                                </b-form-select>
                                            </b-form-group>
                                        </b-col>
                                    </b-row>
                                </template>

                                <template v-else-if="cfg_field.type == 'bool'">
                                    <b-row>
                                        <b-col md='6'>
                                            <b-form-group
                                                label="Truthy Value"
                                                label-class="form-control-label"
                                                description="The value that represents 'true'">
                                                <b-form-input v-model="cfg_field.config.true" placeholder="Truthy Value"></b-form-input>
                                            </b-form-group>
                                        </b-col>
                                        <b-col md='6'>
                                            <b-form-group
                                                label="Falsy Value"
                                                label-class="form-control-label"
                                                description="The value that represents 'false'">
                                                <b-form-input v-model="cfg_field.config.false" placeholder="Falsy Value"></b-form-input>
                                            </b-form-group>
                                        </b-col>
                                    </b-row>
                                </template>

                                <template v-else>
                                    No Additional Properties to configure.
                                </template>
                            </b-card-text>
                        </b-card>
                        <b-card :title="'Conditional Logic (' + cfg_field.logic.length + ')'">
                            <b-card-text>
                                <template v-for="(lfield, lindex) in cfg_field.logic" align-v>
                                    <validation-observer :key="refBase+'-cl-edt-'+lindex.toString()" slim>
                                        <b-row>
                                            <b-col>
                                                <validation-provider
                                                    rules="required"
                                                    vid="scope"
                                                    name="scope"
                                                    v-slot="{ errors }">
                                                    <b-form-select
                                                        v-model="lfield.scope"
                                                        size="sm"
                                                        vid="scope"
                                                        name="scope">
                                                        <template #first>
                                                            <b-form-select-option value="null" disabled>Select Scope</b-form-select-option>
                                                            <b-form-select-option value="parent" :disabled="!parent">Parent</b-form-select-option>
                                                            <b-form-select-option value="siblings" :disabled="!parent">Sibling</b-form-select-option>
                                                            <b-form-select-option value="children" :disabled="cfg_field && !cfg_field.children">Child</b-form-select-option>
                                                        </template>
                                                    </b-form-select>
                                                    <span class='validation-failed'>{{ errors[0] }}</span>
                                                </validation-provider>
                                            </b-col>
                                            <b-col>
                                                <validation-provider
                                                    rules="required"
                                                    vid="field"
                                                    name="field"
                                                    v-slot="{ errors }">
                                                    <b-form-select
                                                        v-model="lfield.field"
                                                        size="sm"
                                                        vid="field"
                                                        name="field"
                                                        :options="lfield.scope ? cfg_fields[lfield.scope] : []">
                                                        <template #first>
                                                            <b-form-select-option value="null" disabled>Select Field</b-form-select-option>
                                                        </template>
                                                    </b-form-select>
                                                    <span class='validation-failed'>{{ errors[0] }}</span>
                                                </validation-provider>
                                            </b-col>
                                            <b-col>
                                                <validation-provider
                                                    rules="required"
                                                    vid="operator"
                                                    name="operator"
                                                    v-slot="{ errors }">
                                                    <b-form-select
                                                        v-model="lfield.operator"
                                                        size="sm"
                                                        vid="operator"
                                                        name="operator"
                                                        :options="cl_operators">
                                                        <template #first>
                                                            <b-form-select-option value="null" disabled>Select Operator</b-form-select-option>
                                                        </template>
                                                    </b-form-select>
                                                    <span class='validation-failed'>{{ errors[0] }}</span>
                                                </validation-provider>
                                            </b-col>
                                            <b-col>
                                                <validation-provider
                                                    rules="required"
                                                    vid="value"
                                                    name="value"
                                                    v-slot="{ errors }">
                                                    <b-form-input
                                                        v-if=""
                                                        v-model="lfield.value"
                                                        size="sm"
                                                        vid="value"
                                                        name="value"
                                                        placeholder="Value">
                                                    </b-form-input>
                                                    <span class='validation-failed'>{{ errors[0] }}</span>
                                                </validation-provider>
                                            </b-col>
                                            <!-- Controls -->
                                            <b-col class='text-right'>
                                                <b-button-group size="sm">
                                                    <b-button
                                                        title="Delete Row"
                                                        variant="primary-outline"
                                                        @click="delLogicRow(field, lindex)">
                                                        <b-icon icon="dash-circle" aria-hidden="true"></b-icon>
                                                    </b-button>
                                                    <b-button
                                                        title="Move Up"
                                                        variant="primary-outline"
                                                        @click="moveUpLogicRow(field, lindex)">
                                                        <b-icon icon="arrow-up-circle" aria-hidden="true"></b-icon>
                                                    </b-button>
                                                    <b-button
                                                        title="Move Down"
                                                        variant="primary-outline"
                                                        @click="moveDownLogicRow(field, lindex)">
                                                        <b-icon icon="arrow-down-circle" aria-hidden="true"></b-icon>
                                                    </b-button>
                                                </b-button-group>
                                            </b-col>
                                        </b-row>
                                    </validation-observer>
                                </template>

                                <b-row v-if="!cfg_field.logic.length">
                                    <b-col>
                                        No conditional logic rules defined for this field.
                                    </b-col>
                                </b-row>

                                <!-- Add New Logic Row -->
                                <b-row><b-col><hr></b-col></b-row>

                                <validation-observer v-slot="{ validate, reset }" slim>
                                    <b-row>
                                        <b-col>
                                            <validation-provider
                                                rules="required"
                                                vid="scope"
                                                name="scope"
                                                v-slot="{ errors }">
                                                <b-form-select
                                                    v-model="add_cl.scope"
                                                    size="sm"
                                                    vid="scope"
                                                    name="scope">
                                                    <template #first>
                                                        <b-form-select-option value="null" disabled>Select Scope</b-form-select-option>
                                                        <b-form-select-option value="parent" :disabled="!parent">Parent</b-form-select-option>
                                                        <b-form-select-option value="siblings" :disabled="!parent">Sibling</b-form-select-option>
                                                        <b-form-select-option value="children" :disabled="cfg_field && !cfg_field.children">Child</b-form-select-option>
                                                    </template>
                                                </b-form-select>
                                                <span class='validation-failed'>{{ errors[0] }}</span>
                                            </validation-provider>
                                        </b-col>
                                        <b-col>
                                            <validation-provider
                                                rules="required"
                                                vid="field"
                                                name="field"
                                                v-slot="{ errors }">
                                                <b-form-select
                                                    v-model="add_cl.field"
                                                    size="sm"
                                                    vid="field"
                                                    name="field"
                                                    :options="add_cl.scope ? cfg_fields[add_cl.scope] : []">
                                                    <template #first>
                                                        <b-form-select-option value="null" disabled>Select Field</b-form-select-option>
                                                    </template>
                                                </b-form-select>
                                                <span class='validation-failed'>{{ errors[0] }}</span>
                                            </validation-provider>
                                        </b-col>
                                        <b-col>
                                            <validation-provider
                                                rules="required"
                                                vid="operator"
                                                name="operator"
                                                v-slot="{ errors }">
                                                <b-form-select
                                                    v-model="add_cl.operator"
                                                    size="sm"
                                                    vid="operator"
                                                    name="operator"
                                                    :options="cl_operators">
                                                    <template #first>
                                                        <b-form-select-option value="null" disabled>Select Operator</b-form-select-option>
                                                    </template>
                                                </b-form-select>
                                                <span class='validation-failed'>{{ errors[0] }}</span>
                                            </validation-provider>
                                        </b-col>
                                        <b-col>
                                            <validation-provider
                                                rules="required"
                                                vid="value"
                                                name="value"
                                                v-slot="{ errors }">
                                                <b-form-input
                                                    v-model="add_cl.value"
                                                    size="sm"
                                                    vid="value"
                                                    name="value"
                                                    placeholder="Value">
                                                </b-form-input>
                                                <span class='validation-failed'>{{ errors[0] }}</span>
                                            </validation-provider>
                                        </b-col>
                                        <!-- Controls -->
                                        <b-col class='text-right'>
                                            <b-button-group size="sm">
                                                <b-button
                                                    title="Add Rule"
                                                    variant="primary"
                                                    @click="addLogicRow(cfg_field, index, validate, reset)">
                                                    Add Rule
                                                </b-button>
                                            </b-button-group>
                                        </b-col>
                                    </b-row>
                                </validation-observer>
                            </b-card-text>
                        </b-card>
                    </template>
                </template>

                <template #modal-footer="{ ok, cancel, hide }">
                    <b-button variant="outline-primary" class="ml-auto" @click="closeFieldConfigDialog">Close</b-button>
                </template>

            </b-modal>

        </template>
    </div>
</template>
<script>

export default {
    name: 'JSONFieldMapper',
    props: {
        parentObj: { // parent Component (containing dataFields)
            type: Object,
            default: null
        },
        parent: { // parent field object (element in dataFields)
            type: Object,
            default: null
        },
        meta: { // additional meta data to assist with parsing
            type: Object,
            default: {}
        },
        fields: { // input data that gets morphed (also the output)
            type: Array,
            required: true
        },
        targets: {
            type: Array,
            required: true
        },
        refBase: {
            type: String,
            default: 'jfm'
        },
    },
    created(){
        this.dataFields = this.fields;
        this.thisNode   = this;
    },
    data(){
        return {

            dataFields: [],
            showFieldConfigDialog: false,

            cl_operators: [
                { value: "<",  text: "Less Than" },
                { value: "<=", text: "Less Than/Equal" },
                { value: "==", text: "Equals" },
                { value: "!=", text: "Not Equals" },
                { value: ">",  text: "Greater Than" },
                { value: ">=", text: "Greater Than/Equal" },
                { value: "ct", text: "Contains" }
            ],

            add_cl: {
                scope: null,
                field: null,
                operator: null,
                value: null
            },

            cfg_field: null,
            cfg_fields: {
                parents:  [],
                siblings: [],
                children: []
            }
        }
    },
    methods: {

        addFieldRow(index, validate)
        {
            validate().then(success => {
                if (!success) { return; }
                this.dataFields.splice(index+1, 0, {
                    source:   null,
                    type:     null,
                    target:   null,
                    config:   null,
                    logic:    [],
                    children: []
                });
            });
        },

        delFieldRow(index)
        {
            this.dataFields.splice(index, 1);
        },

        moveUpFieldRow(index)
        {
            if(index >= 1){
                let obj = JSON.parse(JSON.stringify(this.dataFields[index]));
                this.dataFields.splice(index, 1);
                this.dataFields.splice(index-1, 0, obj);
            }
        },

        moveDownFieldRow(index)
        {
            if(index < this.dataFields.length-1){
                let obj = JSON.parse(JSON.stringify(this.dataFields[index]));
                this.dataFields.splice(index, 1);
                this.dataFields.splice(index+1, 0, obj);
            }
        },

        changeFieldRowType(field)
        {
            // Change (Or Set) Config Object
            field.config = this.getConfigByType(field.type);

            // Clear Children if Array or Object
            if(['object', 'array'].indexOf(field.type) > -1){

                // Remove Old Children (Switching to Object/Array)
                if(field.children && field.children.length){
                    while(field.children.length){
                        field.children.pop();
                    }
                }

                // Add One Empty Row
                field.children.push({
                    source:   null,
                    type:     null,
                    target:   null,
                    config:   null,
                    logic:    [],
                    children: []
                });

            } else {

                // Remove Old Children (Switching from Object/Array)
                if(field.children && field.children.length){
                    while(field.children.length){
                        field.children.pop();
                    }
                }
            }
        },

        getConfigByType(type)
        {
            let obj = {};

            switch(type){
                case 'array':
                    obj.variable_members = false;
                    obj.accumulator = false;
                break;
                case 'object':
                break;
                case 'number':
                    obj.format = 'float';
                break;
                case 'text':
                    obj.offset = '';
                    obj.limit = '';
                break;
                case 'bool':
                    obj.true = 'true';
                    obj.false = 'false';
                break;
            }
            return obj;
        },

        /* Config Dialog Open/Close */

        openFieldConfigDialog(field, index)
        {
            this.cfg_field = field;
            this.populateLogicDropdowns(field);
            this.showFieldConfigDialog = true;
        },

        closeFieldConfigDialog()
        {
            this.clearLogicDropdowns();
            this.resetAddLogicModel();
            this.showFieldConfigDialog = false;
            this.cfg_field = null;
        },

        /* Config - Conditional Logic */

        resetAddLogicModel()
        {
            this.add_cl.scope = null;
            this.add_cl.field = null;
            this.add_cl.operator = null;
            this.add_cl.value = null;
        },

        addLogicRow(field, index, validate, resetValidation)
        {
            validate()
            .then(success => {
                if (!success) { return; }
                let obj = JSON.parse(JSON.stringify(this.add_cl));
                field.logic.push(obj);
                this.resetAddLogicModel();
                resetValidation();
            });
        },

        delLogicRow(field, index)
        {
            field.logic.splice(index, 1);
        },

        moveUpLogicRow(field, index)
        {
            if(index >= 1){
                let obj = JSON.parse(JSON.stringify(field.logic[index]));
                field.logic.splice(index, 1);
                field.logic.splice(index-1, 0, obj);
            }
        },

        moveDownLogicRow(field, index)
        {
            if(index < field.logic.length-1){
                let obj = JSON.parse(JSON.stringify(field.logic[index]));
                field.logic.splice(index, 1);
                field.logic.splice(index+1, 0, obj);
            }
        },

        populateLogicDropdowns(field)
        {
            // Parents
            if(this.parentObj && this.parentObj.parentObj && this.parentObj.parentObj.dataFields){
                this.parentObj.parentObj.dataFields.map((i) => {
                    if(i.source !== null){
                        this.cfg_fields.parents.push({ value: i.source, text: i.source });
                    }
                });
            }
            // Siblings
            if(this.dataFields){
                this.dataFields.map((i) => {
                    if(i.source !== null && i.source != field.source){
                        this.cfg_fields.siblings.push({ value: i.source, text: i.source });
                    }
                });
            }
            // Children
            if(field && field.children){
                field.children.map((i) => {
                    if(i.source !== null){
                        this.cfg_fields.children.push({ value: i.source, text: i.source });
                    }
                });
            }
        },

        clearLogicDropdowns()
        {
            // Clear Parents
            if(this.cfg_fields.parents.length){
                while(this.cfg_fields.parents.length){
                    this.cfg_fields.parents.pop();
                }
            }
            // Clear Siblings
            if(this.cfg_fields.siblings.length){
                while(this.cfg_fields.siblings.length){
                    this.cfg_fields.siblings.pop();
                }
            }
            // Clear Children
            if(this.cfg_fields.children.length){
                while(this.cfg_fields.children.length){
                    this.cfg_fields.children.pop();
                }
            }
        }
    }
}

</script>
<style>

.jsonfieldmapper {
    margin:0;
    padding:0 4px;
    border:1px solid #ddd;
    border-radius:0.25em;
}

.jsonfieldmapper .form-group {
    margin-bottom:0.25em;
}

.jsonfieldmapper_props .custom-select-sm,
.jsonfieldmapper .custom-select-sm {
    height:2.6em;
}

.jsonfieldmapper_props .row,
.jsonfieldmapper .row {
    margin-left:-4px;
    margin-right:-4px;
    margin-top: 4px;
    margin-bottom: 4px;
}

.jsonfieldmapper_props .row .col,
.jsonfieldmapper .row .col {
    padding-left:4px;
    padding-right:4px;
}

.jsonfieldmapper_props .validation-failed,
.jsonfieldmapper .validation-failed {
    font-size: 60%;
    color: #fb6340;
}

.jsonfieldmapper_props hr {
    margin:0.5em 0;
}

.jsonfieldmapper_props p {
    line-height:1;
}

.jsonfieldmapper_props .custom-checkbox .custom-control-label:before,
.jsonfieldmapper_props .custom-checkbox .custom-control-label:after {
    top:0px;
}

.jsonfieldmapper_props .badge.badge-primary {
    background-color: #00a04c;
    color: #ffffff;
}

.jsonfieldmapper_props .badge {
    text-transform: inherit;
}

.jsonfieldmapper option,
.jsonfieldmapper_props option {
    color:#000000;
}

.jsonfieldmapper option:disabled,
.jsonfieldmapper_props option:disabled {
    color:#dddddd;
}



</style>