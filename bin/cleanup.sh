#!/bin/bash

tmpFolder="/tmp"

find $(dirname "$(pwd)")$tmpFolder -mmin +15 -delete