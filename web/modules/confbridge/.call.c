#include <stdio.h>
#include <unistd.h>
int main(int argc, int *argv[])
{
  setuid(geteuid());
  setgid(getegid());
  return execl("./.call.php",".call.php", NULL);
}