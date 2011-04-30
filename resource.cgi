#!/bin/bash
      
echo "Content-type: text/html; charset=cp1251; \n\n";
      
echo
      
export A=`whoami`
      
echo "<HTML>"
      
echo "Список процессов пользователя $A :<br>"
      
echo "<pre>"
      
ps u -U $A  | sed s/"<"/'&lt'/g | sed s/">"/'>'/g | sed s/\$/""/g| sed s/\\n/"
"/g | grep -v -E 'bash|ps|sed|grep'
      
echo "</pre>"
      
date
      
echo "</HTML>"