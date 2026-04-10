###############################################################################
#  SVXlink RSSI module event handlers by Bob Iannucci (W6EI)
#
#  This module enables the user to get signal strength reports of the signal
#  as seen by the repeater.
#
#  Options include
#    Logging to the syslog
#    Voice announcement in S-units
#    DTMF reporting of dBM
#
###############################################################################

namespace eval RSSI {
    #
    # Check if this module is loaded in the current logic core
    #
    if {![info exists CFG_ID]} {
        return
    }


    #
    # Extract the module name from the current namespace
    #
    set module_name [namespace tail [namespace current]]

    #
    # A convenience function for printing out information prefixed by the
    # module name.
    #
    #   msg - The message to print
    #
    proc printInfo {msg} {
        variable module_name
        puts "$module_name: $msg"
    }


    #
    # Executed when this module is being activated
    #
    proc activating_module {} {
        variable module_name
        Module::activating_module $module_name
    }


    #
    # Executed when this module is being deactivated.
    #
    proc deactivating_module {} {
        variable module_name
        Module::deactivating_module $module_name
    }

    #
    # Executed when the inactivity timeout for this module has expired.
    #
    proc timeout {} {
        variable module_name
        Module::timeout $module_name
    }

    #
    # Executed when playing of the help message for this module has been requested.
    #
    proc play_help {} {
        variable module_name
        Module::play_help $module_name
    }

    #
    # Executed when the state of this module should be reported on the radio
    # channel. The rules for when this function is called are:
    #
    # When a module is active:
    # * At manual identification the status_report function for the active module is
    #   called.
    # * At periodic identification no status_report function is called.
    #
    # When no module is active:
    # * At both manual and periodic (long variant) identification the status_report
    #   function is called for all modules.
    #
    proc status_report {} {
        printInfo "status_report called..."
    }

    #
    # Called when an illegal command has been entered
    #
    #   cmd - The received command
    #
    proc unknown_command {cmd} {
        playNumber $cmd
        playMsg "unknown_command"
    }

    # end of namespace
}


#
# This file has not been truncated
#
