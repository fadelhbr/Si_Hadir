package com.teamone.sihadir.model;

import com.google.gson.annotations.SerializedName;
import java.util.Map;

public class ScheduleResponse {
    @SerializedName("success")
    private boolean success;

    @SerializedName("schedule")
    private Map<String, Map<String, String>> schedule;

    public boolean isSuccess() {
        return success;
    }

    public Map<String, Map<String, String>> getSchedule() {
        return schedule;
    }
}