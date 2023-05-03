export default {
  methods: {
    
    // Calculate Node's Last Reading Date Color
    calcLastReadingColor(obj)
    {
      let color = 'black';
      if(typeof obj.date_diff !== "undefined" && obj.date_diff != 'N/A'){
        if(obj.date_diff.h < 3){ color = 'green'; }
        if(obj.date_diff.h >= 3 && obj.date_diff.h < 6){ color = 'orange'; }
        if(obj.date_diff.h >= 6){ color = 'red'; }
        if(obj.date_diff.d > 0 || obj.date_diff.days > 0){ color = 'red'; }
        if(obj.date_diff.y > 0 || obj.date_diff.m > 0 || obj.date_diff.d > 0 || obj.date_diff.days > 0){ color = 'red'; }
      }
      return color;
    },

    isAdmin(email = '')
    {
      return this.$store.getters.isAdmin(email);
    },

    isDistributor(email = '')
    {
      return this.$store.getters.isDistributor(email);
    },

    isRestricted()
    {
      return this.$store.getters.isRestricted();
    },

    userCan(permission, subsystem, object_id = null, object_type = '')
    {
      return this.$store.getters.userCan(permission, subsystem, object_id, object_type);
    },

    userLimits(permission, subsystem, object_type = ''){
      return this.$store.getters.userLimits(permission, subsystem, object_type);
    },

    convertNodeTypeToGraphRouteName(type){
      if(type == 'Soil Moisture'){
        return 'soil_moisture_graph';
      } else if(type == 'Nutrients'){
        return 'nutrients_graph';
      } else if(type == 'Wells'){
        return 'well_controls_graph';
      } else if(type == 'Water Meter'){
        return 'meters_graph';
      } else {
        return '';
      }
    },

    convertNodeTypeToSubsystem(type){
      if(type == 'Soil Moisture'){
        return 'Soil Moisture';
      } else if(type == 'Nutrients'){
        return 'Nutrients';
      } else if(type == 'Wells'){
        return 'Well Controls';
      } else if(type == 'Water Meter'){
        return 'Meters';
      } else {
        return '';
      }
    },

    /* To be used exclusively with Sensor Dropdowns / Checkbox Lists */
    convertToInches(val){
      val = val.replace('mm','');
      val = parseInt(parseInt(val) / 25) + '"';
      return val;
    },

    /* " */
    convertToIndex(val){
      val = val.replace('mm','');
      val = parseInt(parseInt(val) / 100);
      return val;
    },

    truncateString(str){
      return str.length <= 24 ? str: (str.substring(0, 21) + '...');
    }

  }
}
